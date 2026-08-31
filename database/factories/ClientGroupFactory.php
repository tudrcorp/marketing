<?php

namespace Database\Factories;

use App\Models\ClientGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientGroup>
 */
class ClientGroupFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'color' => fake()->hexColor(),
            'responsible_name' => fake()->name(),
            'responsible_email' => fake()->safeEmail(),
            'responsible_phone' => '04'.fake()->numerify('#########'),
            'created_by_id' => User::factory(),
        ];
    }
}
