<?php

namespace Database\Factories;

use App\Marketing\BirthdayNotificationAudience;
use App\Marketing\BirthdayNotificationChannel;
use App\Models\MassNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MassNotification>
 */
class MassNotificationFactory extends Factory
{
    protected $model = MassNotification::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => 'Campaña '.fake()->words(3, true),
            'copy' => fake()->paragraph(),
            'channels' => [BirthdayNotificationChannel::Email->value],
            'audiences' => [BirthdayNotificationAudience::Collaborators->value],
            'recipient_ids' => null,
            'content_type' => null,
            'attachment' => null,
            'created_by_id' => User::factory(),
        ];
    }
}
