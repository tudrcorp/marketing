<?php

namespace Database\Factories;

use App\Marketing\BirthdayNotificationAudience;
use App\Marketing\BirthdayNotificationChannel;
use App\Models\BirthdayNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BirthdayNotification>
 */
class BirthdayNotificationFactory extends Factory
{
    protected $model = BirthdayNotification::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Felicitación de cumpleaños '.fake()->monthName(),
            'image' => null,
            'copy' => fake()->paragraph(),
            'channels' => [BirthdayNotificationChannel::Email->value],
            'audiences' => [BirthdayNotificationAudience::Collaborators->value],
            'created_by_id' => User::factory(),
        ];
    }
}
