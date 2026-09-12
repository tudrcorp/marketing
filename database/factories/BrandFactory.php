<?php

namespace Database\Factories;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    protected $model = Brand::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'color_hex' => fake()->hexColor(),
            'brand_voice' => fake()->sentence(12),
            'drive_url' => 'https://drive.google.com/drive/folders/'.Str::random(16),
            'canva_url' => 'https://www.canva.com/design/'.Str::random(12),
            'ctas' => [
                ['label' => 'CTA agenda', 'value' => 'Agenda tu cita hoy mismo por WhatsApp.'],
            ],
            'hashtag_groups' => [
                ['label' => 'Generales', 'value' => '#TuDoctorGroup #Salud #Bienestar'],
            ],
            'quick_links' => [
                ['label' => 'Sitio web', 'value' => 'https://tudoctorgroup.com'],
            ],
            'is_active' => true,
            'created_by_id' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
