<?php

namespace App\Services\Pharmacy\StockImport;

use App\Jobs\CommitPharmacyStockImport;
use App\Models\InventoryItem;
use App\Models\Medicine;
use App\Models\MedicinePharmacyStock;
use App\Models\PharmacyStockImport;
use App\Models\StockBatch;
use App\Modules\Pharmacy\Services\PharmacyStockReportService;
use App\Services\Pharmacy\PharmacyPriceService;
use App\Services\Pharmacy\PharmacyStockOpsService;
use App\Support\CsvSafe;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Upload → map columns → preview → commit, for a pharmacy's stock spreadsheet.
 *
 * PROVENANCE. Nothing here writes `medicine_pharmacy_stocks` directly. A
 * committed import becomes an ordinary verified goods receipt (delivery mode)
 * or count corrections plus a receipt (stocktake mode) through
 * PharmacyStockOpsService, whose syncFinder() publishes through
 * PharmacyStockReportService::report() with source_system = 'portal' — the
 * pharmacy desk's own value, already on MedicinePharmacyStock::LIVE_SOURCE_SYSTEMS.
 * An import IS the pharmacy desk reporting its stock (an authenticated
 * pharmacy user, at their own facility, confirming every line), so it is live
 * data by the same rule, and the allow-list is not widened.
 */
class PharmacyStockImportService
{
    public const MAX_BYTES = 5 * 1024 * 1024;
    /** Above this many lines to write, the commit runs on the `imports` queue. */
    public const QUEUE_THRESHOLD = 300;
    /** Uncommitted imports (and their cell values) are dropped after this many hours. */
    public const STALE_HOURS = 24;
    public const REFERENCE_IMPORT = 'pharmacy_stock_import';

    public function __construct(
        private readonly SpreadsheetReader $reader,
        private readonly ColumnMapper $mapper,
        private readonly PharmacyStockOpsService $ops,
        private readonly PharmacyPriceService $prices,
        private readonly PharmacyStockReportService $reports,
    ) {
    }

    // ── 1. Upload ─────────────────────────────────────────────────────────

    /** @throws StockImportException */
    public function createFromUpload(string $facilityId, ?string $actorId, UploadedFile $file): PharmacyStockImport
    {
        if ($file->getSize() > self::MAX_BYTES) {
            throw new StockImportException('err_too_large');
        }

        // The temporary upload is read in place and never copied into storage;
        // it is deleted below whatever happens.
        $path = $file->getRealPath();

        try {
            $parsed = $this->reader->read($path);
        } finally {
            if ($path && is_file($path)) {
                @unlink($path);
            }
        }

        $rows      = $parsed['rows'];
        $headerRow = $this->mapper->headerRow($rows);
        $headers   = $rows[$headerRow] ?? [];
        $data      = array_slice($rows, $headerRow + 1);

        if (count($data) > SpreadsheetReader::MAX_ROWS) {
            throw new StockImportException('err_too_many_rows', ['max' => SpreadsheetReader::MAX_ROWS]);
        }
        if ($data === []) {
            throw new StockImportException('err_no_data_rows');
        }

        return PharmacyStockImport::create([
            'facility_id'   => $facilityId,
            'uploaded_by'   => $actorId,
            'status'        => 'mapping',
            'mode'          => 'delivery',
            'file_format'   => $parsed['format'],
            'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 190),
            'header_row'    => $headerRow,
            'row_count'     => count($data),
            'headers'       => $headers,
            'rows'          => $data,
            'mapping'       => $this->mapper->guess($headers),
        ]);
    }

    // ── 2. Mapping + validation ───────────────────────────────────────────

    /**
     * @param  array<string, int|null>  $mapping field => column index
     */
    public function applyMapping(PharmacyStockImport $import, array $mapping, string $mode): PharmacyStockImport
    {
        $clean = array_fill_keys(ColumnMapper::FIELDS, null);
        $width = count($import->headers ?? []);
        foreach (ColumnMapper::FIELDS as $field) {
            $col = $mapping[$field] ?? null;
            $clean[$field] = ($col === null || $col === '' || (int) $col < 0 || (int) $col >= max($width, 1)) ? null : (int) $col;
        }

        $import->mapping = $clean;
        $import->mode    = in_array($mode, PharmacyStockImport::MODES, true) ? $mode : 'delivery';
        $import->results = $this->analyse($import);
        $import->status  = 'preview';
        $import->error_count = count(array_filter($import->results, fn ($r) => $r['status'] === 'error'));
        $import->save();

        return $import;
    }

    /** @return list<array<string, mixed>> one result per data row */
    public function analyse(PharmacyStockImport $import): array
    {
        $matcher = new MedicineMatcher();
        $map     = $import->mapping;
        $results = [];
        $seen    = [];
        $today   = now()->toDateString();

        foreach ($import->rows ?? [] as $i => $row) {
            $cell = fn (string $f) => $map[$f] !== null ? trim((string) ($row[$map[$f]] ?? '')) : '';

            if (implode('', $row) === '') {
                continue; // a blank line is not an error
            }

            $errors = [];
            $line   = [
                'line'          => $import->header_row + 2 + $i, // 1-based sheet row
                'medicine_text' => mb_substr($cell('medicine') !== '' ? $cell('medicine') : $cell('inn'), 0, 190),
                'strength'      => mb_substr($cell('strength'), 0, 60),
                'form'          => mb_substr($cell('form'), 0, 60),
                'pack'          => mb_substr($cell('pack'), 0, 60),
                'batch'         => mb_substr($cell('batch'), 0, 80),
                'barcode'       => mb_substr($cell('barcode'), 0, 40),
                'quantity'      => null,
                'unit_price'    => null,
                'unit_cost'     => null,
                'expiry'        => null,
            ];

            $match = $matcher->match([
                'medicine' => $cell('medicine'), 'inn' => $cell('inn'), 'strength' => $cell('strength'),
                'form' => $cell('form'), 'atc' => $cell('atc'),
            ]);
            $line += [
                'medicine_id'   => $match['medicine_id'],
                'medicine_name' => $match['name'],
                'method'        => $match['method'],
                'confidence'    => $match['confidence'],
            ];

            if ($line['medicine_text'] === '') {
                $errors[] = ['err_row_no_medicine', []];
            } elseif ($match['method'] === 'unmatched') {
                $errors[] = ['err_row_unmatched', []];
            } elseif ($match['method'] === 'ambiguous') {
                $errors[] = ['err_row_ambiguous', []];
            }

            $qtyRaw = $cell('quantity');
            $qty    = ValueParser::wholeNumber($qtyRaw);
            if ($qtyRaw === '' || $qty === null) {
                $errors[] = ['err_row_quantity', ['value' => mb_substr($qtyRaw, 0, 30)]];
            } elseif ($qty > 1000000) {
                $errors[] = ['err_row_quantity_high', []];
            } elseif ($qty === 0 && $import->mode === 'delivery') {
                $errors[] = ['err_row_quantity_zero', []];
            }
            $line['quantity'] = $qty;

            foreach (['unit_price', 'unit_cost'] as $f) {
                $raw = $cell($f);
                if ($raw !== '') {
                    $v = ValueParser::francs($raw);
                    if ($v === null || $v > PharmacyPriceService::MAX_PRICE_XAF) {
                        $errors[] = ['err_row_' . $f, ['value' => mb_substr($raw, 0, 30)]];
                    }
                    $line[$f] = $v;
                }
            }

            $expRaw = $cell('expiry');
            if ($expRaw !== '') {
                $exp = ValueParser::date($expRaw);
                if ($exp === null) {
                    $errors[] = ['err_row_expiry', ['value' => mb_substr($expRaw, 0, 30)]];
                } elseif ($exp <= $today) {
                    $errors[] = ['err_row_expired', ['date' => $exp]];
                }
                $line['expiry'] = $exp;
            }

            // Only a line that will be imported claims its medicine + batch: a
            // rejected line is never written, so it cannot be duplicated.
            if ($match['medicine_id']) {
                $key = $match['medicine_id'] . '|' . mb_strtolower($line['batch']);
                if (isset($seen[$key])) {
                    $errors[] = ['err_row_duplicate', ['line' => $seen[$key]]];
                } elseif ($errors === []) {
                    $seen[$key] = $line['line'];
                }
            }

            $line['errors'] = $errors;
            $line['status'] = $errors !== [] ? 'error' : ($match['method'] === 'fuzzy' ? 'review' : 'ok');
            $results[] = $line;
        }

        return $results;
    }

    // ── 3. Commit ─────────────────────────────────────────────────────────

    /** @return list<array<string, mixed>> */
    public function linesToWrite(PharmacyStockImport $import, bool $acceptFuzzy): array
    {
        return array_values(array_filter($import->results ?? [], fn ($r) => $r['status'] === 'ok' || ($acceptFuzzy && $r['status'] === 'review')));
    }

    /** Commit now, or hand a large file to the imports queue. Returns true when queued. */
    public function commit(PharmacyStockImport $import, bool $acceptFuzzy, ?string $actorId): bool
    {
        if (count($this->linesToWrite($import, $acceptFuzzy)) > self::QUEUE_THRESHOLD) {
            $import->update(['status' => 'queued']);
            CommitPharmacyStockImport::dispatch($import->id, $acceptFuzzy, $actorId)->onQueue('imports');

            return true;
        }

        $this->commitNow($import, $acceptFuzzy, $actorId);

        return false;
    }

    public function commitNow(PharmacyStockImport $import, bool $acceptFuzzy, ?string $actorId): void
    {
        $lines = $this->linesToWrite($import, $acceptFuzzy);

        if ($lines === []) {
            $import->update(['status' => 'failed', 'failure' => 'no_valid_lines']);

            return;
        }

        try {
            $receiptId = DB::transaction(function () use ($import, $lines, $actorId) {
                $facilityId = $import->facility_id;
                $receipt    = [];
                $items      = [];

                foreach ($lines as $line) {
                    $medicine = Medicine::query()->active()->findOrFail($line['medicine_id']);
                    $item     = $this->ops->itemFor($facilityId, $medicine, $actorId);
                    $items[$item->id] = [$item, $line];
                    $lot = $line['batch'] !== '' ? $line['batch'] : null;

                    if ($import->mode === 'stocktake') {
                        $batch = StockBatch::query()
                            ->where('facility_id', $facilityId)
                            ->where('inventory_item_id', $item->id)
                            ->where('status', 'active')
                            ->when($lot === null, fn ($q) => $q->whereNull('lot_number'), fn ($q) => $q->where('lot_number', $lot))
                            ->orderBy('created_at')
                            ->first();

                        if ($batch) {
                            $delta = (int) $line['quantity'] - $batch->availableQty();
                            if ($delta !== 0) {
                                $this->ops->adjust($batch, 'count_correction', $delta, __('pharmacy_stock_import.movement_note', [], 'en'), $actorId, self::REFERENCE_IMPORT, $import->id);
                            }
                            continue;
                        }
                        if ((int) $line['quantity'] === 0) {
                            continue; // nothing on the shelf, nothing to record
                        }
                    }

                    $receipt[] = [
                        'medicine_id' => $medicine->id,
                        'quantity'    => (int) $line['quantity'],
                        'lot_number'  => $lot,
                        'expiry_date' => $line['expiry'],
                        'unit_cost'   => $line['unit_cost'],
                    ];
                }

                $receiptModel = $receipt === [] ? null : $this->ops->receiveDelivery($facilityId, [
                    'received_date' => now()->toDateString(),
                    'notes'         => __('pharmacy_stock_import.receipt_note', ['file' => $import->original_name], 'en'),
                ], $receipt, $actorId);

                // Prices and pack sizes, once per medicine (the last line wins).
                $listing = $this->reports->listingFor($facilityId);
                foreach ($items as [$item, $line]) {
                    /** @var InventoryItem $item */
                    if ($line['unit_price'] !== null && (int) $item->fresh()->selling_price_xaf !== (int) $line['unit_price']) {
                        $this->prices->setPrice($item->fresh(), (int) $line['unit_price'], $actorId, 'import');
                    }
                    if ($listing && $line['pack'] !== '') {
                        MedicinePharmacyStock::query()
                            ->where('care_facility_id', $listing->id)
                            ->where('medicine_id', $item->medicine_id)
                            ->update(['pack_size' => $line['pack']]);
                    }
                }

                return $receiptModel?->id;
            });
        } catch (\Throwable $e) {
            Log::warning('pharmacy stock import failed', ['import' => $import->id, 'error' => $e->getMessage()]);
            $import->update(['status' => 'failed', 'failure' => mb_substr(class_basename($e), 0, 255)]);

            return;
        }

        $written = array_column($lines, 'line');
        $import->update([
            'status'           => 'committed',
            'goods_receipt_id' => $receiptId,
            'imported_count'   => count($lines),
            'error_count'      => count($import->results ?? []) - count($lines),
            // Keep only what the error report needs; the sheet's cells go.
            'results'          => array_values(array_filter($import->results ?? [], fn ($r) => ! in_array($r['line'], $written, true))),
            'rows'             => null,
            'committed_at'     => now(),
        ]);
    }

    public function cancel(PharmacyStockImport $import): void
    {
        $import->update(['status' => 'cancelled', 'rows' => null, 'results' => null]);
    }

    /** Drop the cell values of imports nobody finished. */
    public function purgeStale(): void
    {
        PharmacyStockImport::query()
            ->whereIn('status', ['mapping', 'preview'])
            ->where('updated_at', '<', now()->subHours(self::STALE_HOURS))
            ->update(['status' => 'cancelled', 'rows' => null, 'results' => null]);
    }

    // ── 4. Error report ───────────────────────────────────────────────────

    /** @return list<list<mixed>> CSV rows, formula-injection safe */
    public function errorReport(PharmacyStockImport $import): array
    {
        $out = [[
            __('pharmacy_stock_import.report_line'),
            __('pharmacy_stock_import.report_medicine'),
            __('pharmacy_stock_import.report_batch'),
            __('pharmacy_stock_import.report_status'),
            __('pharmacy_stock_import.report_message'),
        ]];

        foreach ($import->results ?? [] as $r) {
            if ($r['status'] === 'ok') {
                continue;
            }
            $messages = array_map(fn ($e) => __('pharmacy_stock_import.' . $e[0], $e[1]), $r['errors'] ?? []);
            if ($r['status'] === 'review' && $messages === []) {
                $messages[] = __('pharmacy_stock_import.review_fuzzy', ['name' => $r['medicine_name'], 'confidence' => $r['confidence']]);
            }
            $out[] = CsvSafe::row([
                $r['line'],
                $r['medicine_text'],
                $r['batch'],
                __('pharmacy_stock_import.status_' . $r['status']),
                implode(' | ', $messages),
            ]);
        }

        return $out;
    }
}
