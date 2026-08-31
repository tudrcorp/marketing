<?php

namespace Database\Factories;

use App\Marketing\CorporateEventRegistrationStatus;
use App\Models\CorporateEvent;
use App\Models\CorporateEventRegistration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CorporateEventRegistration>
 */
class CorporateEventRegistrationFactory extends Factory
{
    protected $model = CorporateEventRegistration::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'corporate_event_id' => CorporateEvent::factory(),
            'full_name' => fake()->name(),
            'document_id' => 'V'.fake()->numerify('########'),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('04#########'),
            'company' => fake()->optional()->company(),
            'audience_source' => null,
            'status' => CorporateEventRegistrationStatus::Registered->value,
            'source' => 'manual',
            'registered_by_id' => User::factory(),
            'registered_at' => now(),
        ];
    }

    public function attended(): static
    {
        return $this->state(fn (): array => [
            'status' => CorporateEventRegistrationStatus::Attended->value,
        ]);
    }
}
