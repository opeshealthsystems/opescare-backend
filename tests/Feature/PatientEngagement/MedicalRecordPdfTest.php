<?php
namespace Tests\Feature\PatientEngagement;

use App\Models\Facility;
use App\Models\Patient;
use App\Models\User;
use App\Services\PatientEngagement\MedicalRecordPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicalRecordPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_summary_generated_for_patient(): void
    {
        if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $this->markTestSkipped('barryvdh/laravel-dompdf not installed — run: composer require barryvdh/laravel-dompdf');
        }

        $patient = Patient::factory()->create([
            'first_name' => 'Chidi',
            'last_name'  => 'Nkemdirim',
        ]);

        $service = new MedicalRecordPdfService();
        $pdf     = $service->generateSummary($patient->id);

        $this->assertIsString($pdf);
        $this->assertGreaterThan(100, strlen($pdf));
    }
}
