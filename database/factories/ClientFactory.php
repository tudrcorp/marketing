<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\ClientGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_group_id' => ClientGroup::factory(),
            'full_name' => fake()->name(),
            'document_id' => fake()->numerify('V-########'),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '04'.fake()->numerify('#########'),
        ];
    }
}
