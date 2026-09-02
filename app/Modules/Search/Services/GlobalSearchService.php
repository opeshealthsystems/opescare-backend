<?php

namespace App\Modules\Search\Services;

use App\Models\AuditEvent;
use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\LabTestAvailability;
use App\Models\OfficialDocument;
use App\Models\Patient;
use App\Models\PharmacyStockAvailability;
use App\Modules\Messaging\Models\Message;
use App\Modules\Partners\Models\Partner;
use Illuminate\Support\Collection;

class GlobalSearchService
{
    public function search(string $query, array $context = []): array
    {
        $query = trim($query);
        $results = collect();

        if ($query === '') {
            return ['query' => $query, 'results' => [], 'counts' => []];
        }

        $results = $results
            ->merge($this->facilityResults($query))
            ->merge($this->documentResults($query))
            ->merge($this->medicineResults($query))
            ->merge($this->labTestResults($query))
            ->merge($this->partnerResults($query))
            ->merge($this->messageResults($query, $context));

        if ($context['include_sensitive'] ?? false) {
            $patientResults = $this->patientResults($query, $context);
            $results = $patientResults->merge($results);
        }

        return [
            'query' => $query,
            'results' => $results->values()->all(),
            'counts' => $results->groupBy('type')->map->count()->all(),
        ];
    }

    private function patientResults(string $query, array $context): Collection
    {
        $like = $this->likeTerm($query);

        return Patient::whereRaw('LOWER(health_id) LIKE ?', [$like])
            ->orWhereRaw('LOWER(first_name) LIKE ?', [$like])
            ->orWhereRaw('LOWER(last_name) LIKE ?', [$like])
            ->limit(10)
            ->get()
            ->map(function (Patient $patient) use ($context, $query) {
                AuditEvent::create([
                    'actor_id' => $context['actor_id'] ?? null,
                    'patient_id' => $patient->id,
                    'action_type' => 'search_patient',
                    'resource_type' => 'global_search',
                    'resource_id' => $patient->id,
                    'reason' => $context['purpose'] ?? 'global_search',
                    'after_state' => ['query' => $query],
                ]);

                return [
                    'type' => 'patient',
                    'title' => trim($patient->first_name.' '.substr((string) $patient->last_name, 0, 1).'.'),
                    'subtitle' => 'Patient identity match',
                    'metadata' => [
                        'id' => $patient->id,
                        'health_id' => $patient->health_id,
                        'sex' => $patient->sex,
                    ],
                ];
            });
    }

    private function facilityResults(string $query): Collection
    {
        $like = $this->likeTerm($query);

        $facilities = Facility::whereRaw('LOWER(name) LIKE ?', [$like])
            ->orWhereRaw('LOWER(license_number) LIKE ?', [$like])
            ->limit(10)
            ->get()
            ->map(fn (Facility $facility) => [
                'type' => 'facility',
                'title' => $facility->name,
                'subtitle' => $facility->type,
                'metadata' => ['id' => $facility->id, 'status' => $facility->status],
            ]);

        $careFacilities = CareFacility::whereRaw('LOWER(facility_name) LIKE ?', [$like])
            ->orWhereRaw('LOWER(city) LIKE ?', [$like])
            ->orWhereRaw('LOWER(license_number) LIKE ?', [$like])
            ->limit(10)
            ->get()
            ->map(fn (CareFacility $facility) => [
                'type' => 'facility',
                'title' => $facility->facility_name,
                'subtitle' => $facility->facility_type,
                'metadata' => ['id' => $facility->id, 'status' => $facility->listing_status],
            ]);

        return $facilities->merge($careFacilities);
    }

    private function documentResults(string $query): Collection
    {
        $like = $this->likeTerm($query);

        return OfficialDocument::whereRaw('LOWER(verification_code) LIKE ?', [$like])
            ->orWhereRaw('LOWER(document_number) LIKE ?', [$like])
            ->orWhereRaw('LOWER(title) LIKE ?', [$like])
            ->limit(10)
            ->get()
            ->map(fn (OfficialDocument $document) => [
                'type' => 'document',
                'title' => $document->title,
                'subtitle' => $document->document_type,
                'metadata' => [
                    'id' => $document->id,
                    'document_number' => $document->document_number,
                    'verification_code' => $document->verification_code,
                    'status' => $document->status,
                ],
            ]);
    }

    private function medicineResults(string $query): Collection
    {
        $like = $this->likeTerm($query);

        return PharmacyStockAvailability::whereRaw('LOWER(medicine_name) LIKE ?', [$like])
            ->orWhereRaw('LOWER(generic_name) LIKE ?', [$like])
            ->orWhereRaw('LOWER(brand_name) LIKE ?', [$like])
            ->limit(10)
            ->get()
            ->map(fn (PharmacyStockAvailability $stock) => [
                'type' => 'medicine',
                'title' => $stock->medicine_name,
                'subtitle' => $stock->availability_status,
                'metadata' => ['id' => $stock->id, 'facility_id' => $stock->facility_id],
            ]);
    }

    private function labTestResults(string $query): Collection
    {
        $like = $this->likeTerm($query);

        return LabTestAvailability::whereRaw('LOWER(test_name) LIKE ?', [$like])
            ->orWhereRaw('LOWER(loinc_code) LIKE ?', [$like])
            ->limit(10)
            ->get()
            ->map(fn (LabTestAvailability $test) => [
                'type' => 'lab_test',
                'title' => $test->test_name,
                'subtitle' => $test->availability_status,
                'metadata' => ['id' => $test->id, 'facility_id' => $test->facility_id],
            ]);
    }

    private function partnerResults(string $query): Collection
    {
        $like = $this->likeTerm($query);

        return Partner::whereRaw('LOWER(legal_name) LIKE ?', [$like])
            ->orWhereRaw('LOWER(trade_name) LIKE ?', [$like])
            ->limit(10)
            ->get()
            ->map(fn (Partner $partner) => [
                'type' => 'partner',
                'title' => $partner->trade_name ?: $partner->legal_name,
                'subtitle' => $partner->partner_type,
                'metadata' => ['id' => $partner->id, 'status' => $partner->status],
            ]);
    }

    private function messageResults(string $query, array $context): Collection
    {
        $authorizedUser = $context['authorized_message_user_id'] ?? null;
        if (! $authorizedUser) {
            return collect();
        }

        return Message::query()
            ->whereRaw('LOWER(body) LIKE ?', [$this->likeTerm($query)])
            ->where(function ($messageQuery) use ($authorizedUser) {
                $messageQuery->where('sender_id', $authorizedUser)
                    ->orWhereHas('thread', fn ($threadQuery) => $threadQuery->where('created_by', $authorizedUser)->orWhere('assigned_to', $authorizedUser));
            })
            ->limit(10)
            ->get()
            ->map(fn (Message $message) => [
                'type' => 'message',
                'title' => 'Message '.$message->uuid,
                'subtitle' => str($message->body)->limit(80)->toString(),
                'metadata' => ['id' => $message->id, 'thread_id' => $message->thread_id],
            ]);
    }

    private function likeTerm(string $query): string
    {
        return '%'.mb_strtolower($query).'%';
    }
}
