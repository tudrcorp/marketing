<?php

namespace Database\Factories;

use App\Marketing\MarketingPermission;
use App\Models\MarketingRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MarketingRole>
 */
class MarketingRoleFactory extends Factory
{
    protected $model = MarketingRole::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->jobTitle();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'permissions' => [MarketingPermission::ViewCalendar],
            'is_system' => false,
        ];
    }

    public function administrator(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Administrador de marketing',
            'slug' => 'administrador-'.fake()->unique()->numerify('###'),
            'permissions' => MarketingPermission::all(),
            'is_system' => true,
        ]);
    }
}
