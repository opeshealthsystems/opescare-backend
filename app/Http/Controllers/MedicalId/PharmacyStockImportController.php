<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\PharmacyStockImport;
use App\Services\Pharmacy\StockImport\ColumnMapper;
use App\Services\Pharmacy\StockImport\PharmacyStockImportService;
use App\Services\Pharmacy\StockImport\StockImportException;
use App\Services\Pharmacy\StockImport\StockTemplate;
use App\Services\Portal\PortalContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Stock import from a spreadsheet — /pharmacy/stock/import (routes/pharmacy_ops.php,
 * capability:pharmacy_stock). Upload → confirm columns → preview → commit.
 *
 * The facility is PortalContextService's active facility, never a request
 * value; another pharmacy's import does not exist here (404).
 */
class PharmacyStockImportController extends Controller
{
    /** Extensions the upload accepts. The PARSER is chosen by the file's bytes, not by this list. */
    public const EXTENSIONS = ['csv', 'tsv', 'txt', 'xlsx', 'xls', 'ods'];

    public function __construct(
        private readonly PortalContextService $context,
        private readonly PharmacyStockImportService $imports,
        private readonly StockTemplate $template,
    ) {
    }

    public function index(): View
    {
        $facilityId = $this->facilityId();
        $this->imports->purgeStale();

        return view('portals.pharmacy.stock_ops.import', [
            'recent'     => PharmacyStockImport::query()->where('facility_id', $facilityId)->latest()->limit(15)->get(),
            'extensions' => self::EXTENSIONS,
            'maxMb'      => PharmacyStockImportService::MAX_BYTES / 1024 / 1024,
        ]);
    }

    public function template(string $format): BinaryFileResponse|\Illuminate\Http\Response
    {
        $this->facilityId();
        $name = __('pharmacy_stock_import.template_filename');

        if ($format === 'csv') {
            return response($this->template->csv(), 200, [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $name . '.csv"',
            ]);
        }

        return response()->download($this->template->xlsx(), $name . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function store(Request $request): RedirectResponse
    {
        $facilityId = $this->facilityId();

        $request->validate([
            'file' => ['required', 'file', 'max:' . (PharmacyStockImportService::MAX_BYTES / 1024)],
        ]);

        $file = $request->file('file');
        $ext  = strtolower($file->getClientOriginalExtension());

        if (in_array($ext, ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'heic', 'tif', 'tiff', 'doc', 'docx', 'odt'], true)) {
            return back()->withErrors(['file' => __('pharmacy_stock_import.err_not_a_spreadsheet')]);
        }
        if (! in_array($ext, self::EXTENSIONS, true)) {
            return back()->withErrors(['file' => __('pharmacy_stock_import.err_extension', ['list' => implode(', ', self::EXTENSIONS)])]);
        }

        try {
            $import = $this->imports->createFromUpload($facilityId, $this->context->actorId(), $file);
        } catch (StockImportException $e) {
            return back()->withErrors(['file' => $e->userMessage()]);
        }

        return redirect()->route('portals.pharmacy.stock.import.mapping', $import->id);
    }

    public function mapping(string $id): View
    {
        $import = $this->find($id);
        abort_unless($import->isOpen(), 404);

        return view('portals.pharmacy.stock_ops.import_mapping', [
            'import'  => $import,
            'fields'  => ColumnMapper::FIELDS,
            'required'=> ColumnMapper::REQUIRED,
            'sample'  => array_slice($import->rows ?? [], 0, 5),
        ]);
    }

    public function storeMapping(Request $request, string $id): RedirectResponse
    {
        $import = $this->find($id);
        abort_unless($import->isOpen(), 404);

        $width = max(count($import->headers ?? []), 1);
        $rules = ['mode' => ['required', Rule::in(PharmacyStockImport::MODES)]];
        foreach (ColumnMapper::FIELDS as $f) {
            $rules["map.$f"] = [in_array($f, ColumnMapper::REQUIRED, true) ? 'required' : 'nullable', 'integer', 'min:0', 'max:' . ($width - 1)];
        }
        $data = $request->validate($rules, [], collect(ColumnMapper::FIELDS)->mapWithKeys(fn ($f) => ["map.$f" => __('pharmacy_stock_import.col_' . $f)])->all());

        $this->imports->applyMapping($import, $data['map'], $data['mode']);

        return redirect()->route('portals.pharmacy.stock.import.preview', $import->id);
    }

    public function preview(string $id): View
    {
        $import = $this->find($id);
        $results = collect($import->results ?? []);

        return view('portals.pharmacy.stock_ops.import_preview', [
            'import'  => $import,
            'results' => $results,
            'counts'  => [
                'ok'     => $results->where('status', 'ok')->count(),
                'review' => $results->where('status', 'review')->count(),
                'error'  => $results->where('status', 'error')->count(),
            ],
        ]);
    }

    public function commit(Request $request, string $id): RedirectResponse
    {
        $import = $this->find($id);
        abort_unless($import->status === 'preview', 404);

        $data = $request->validate(['accept_fuzzy' => ['nullable', 'boolean'], 'confirm' => ['accepted']]);

        $queued = $this->imports->commit($import, (bool) ($data['accept_fuzzy'] ?? false), $this->context->actorId());
        $import->refresh();

        if ($queued) {
            return redirect()->route('portals.pharmacy.stock.import.preview', $import->id)->with('success', __('pharmacy_stock_import.flash_queued'));
        }
        if ($import->status !== 'committed') {
            return redirect()->route('portals.pharmacy.stock.import.preview', $import->id)->withErrors(['import' => __('pharmacy_stock_import.flash_failed')]);
        }

        return redirect()->route('portals.pharmacy.stock.import.preview', $import->id)
            ->with('success', __('pharmacy_stock_import.flash_committed', ['count' => $import->imported_count]));
    }

    public function cancel(string $id): RedirectResponse
    {
        $import = $this->find($id);
        abort_unless($import->isOpen(), 404);
        $this->imports->cancel($import);

        return redirect()->route('portals.pharmacy.stock.import')->with('success', __('pharmacy_stock_import.flash_cancelled'));
    }

    public function errors(string $id): StreamedResponse
    {
        $import = $this->find($id);
        $rows = $this->imports->errorReport($import);

        return response()->streamDownload(function () use ($rows) {
            $h = fopen('php://output', 'w');
            fwrite($h, "\xEF\xBB\xBF");
            foreach ($rows as $row) {
                fputcsv($h, $row, ',', '"', '');
            }
            fclose($h);
        }, 'stock-import-errors-' . substr($import->id, 0, 8) . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function find(string $id): PharmacyStockImport
    {
        return PharmacyStockImport::query()->where('facility_id', $this->facilityId())->findOrFail($id);
    }

    private function facilityId(): string
    {
        $facilityId = $this->context->facilityId();
        abort_unless($facilityId, 403, __('pharmacy_ops.no_facility_context'));

        return $facilityId;
    }
}
