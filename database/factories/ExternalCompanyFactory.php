<?php

namespace Database\Factories;

use App\Marketing\ExternalCompanyType;
use App\Models\ExternalCompany;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalCompany>
 */
class ExternalCompanyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => ExternalCompanyType::Company,
            'company_name' => fake()->company(),
            'legal_name' => fake()->company().' C.A.',
            'document_id' => 'J-'.fake()->numerify('########').'-'.fake()->numerify('#'),
            'phone' => '04'.fake()->numerify('#########'),
            'email' => fake()->unique()->companyEmail(),
            'responsible_name' => fake()->name(),
            'responsible_document_id' => 'V-'.fake()->numerify('########'),
            'responsible_phone' => '04'.fake()->numerify('#########'),
            'responsible_email' => fake()->unique()->safeEmail(),
            'created_by_id' => User::factory(),
        ];
    }

    public function naturalPerson(): static
    {
        return $this->state(fn (): array => [
            'type' => ExternalCompanyType::NaturalPerson,
            'company_name' => fake()->name(),
            'legal_name' => null,
            'document_id' => 'V-'.fake()->numerify('########'),
        ]);
    }
}
