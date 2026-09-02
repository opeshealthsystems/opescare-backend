<?php
namespace Database\Factories;

use App\Models\Facility;
use App\Models\Patient;
use App\Models\RadiologyReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RadiologyReportFactory extends Factory {
    protected $model = RadiologyReport::class;

    public function definition(): array {
        return [
            'patient_id'          => Patient::factory(),
            'facility_id'         => Facility::factory(),
            'ordered_by'          => User::factory(),
            'reported_by'         => User::factory(),
            'modality'            => $this->faker->randomElement(['ct','mri','xray','ultrasound']),
            'body_part'           => $this->faker->randomElement(['Chest','Abdomen','Head','Spine']),
            'study_date'          => now()->subHours(2),
            'clinical_indication' => $this->faker->sentence(),
            'findings'            => $this->faker->paragraph(),
            'impression'          => $this->faker->sentence(),
            'recommendation'      => null,
            'status'              => 'draft',
            'distributed_to'      => [],
        ];
    }
}
