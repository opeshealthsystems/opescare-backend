<?php
namespace Database\Factories;

use App\Models\DocumentTemplate;
use App\Models\OfficialDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

class OfficialDocumentFactory extends Factory
{
    protected $model = OfficialDocument::class;

    public function definition(): array
    {
        return [
            'document_type'     => $this->faker->randomElement(['discharge_summary', 'lab_report', 'referral_letter', 'vaccination_card']),
            'document_number'   => strtoupper($this->faker->unique()->bothify('DOC-????-######')),
            'verification_code' => strtoupper($this->faker->unique()->bothify('VER-????-######')),
            'patient_id'        => null,
            'template_id'       => DocumentTemplate::factory(),
            'template_version'  => '1.0',
            'title'             => $this->faker->sentence(4),
            'status'            => 'released',
            'sensitivity_level' => 'normal',
            'payload_json'      => [],
            'issued_at'         => now()->subDays(rand(1, 90)),
            'expires_at'        => null,
            'is_demo'           => false,
        ];
    }
}
