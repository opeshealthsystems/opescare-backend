<?php
namespace Database\Factories;

use App\Models\DocumentTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentTemplateFactory extends Factory
{
    protected $model = DocumentTemplate::class;

    public function definition(): array
    {
        return [
            'template_code' => strtoupper($this->faker->unique()->bothify('TPL-????-######')),
            'document_type' => $this->faker->randomElement(['discharge_summary', 'lab_report', 'referral_letter', 'vaccination_card']),
            'language'      => 'en',
            'version'       => '1.0',
            'status'        => 'published',
            'html_template' => '<html><body>{{ content }}</body></html>',
            'css_styles'    => null,
            'is_demo'       => false,
        ];
    }
}
