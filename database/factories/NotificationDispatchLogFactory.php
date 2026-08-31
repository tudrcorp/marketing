<?php

namespace Database\Factories;

use App\Marketing\BirthdayNotificationChannel;
use App\Marketing\NotificationDispatchSource;
use App\Marketing\NotificationDispatchStatus;
use App\Models\NotificationDispatchLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationDispatchLog>
 */
class NotificationDispatchLogFactory extends Factory
{
    protected $model = NotificationDispatchLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source' => NotificationDispatchSource::BirthdayTest->value,
            'status' => NotificationDispatchStatus::Failed->value,
            'channel' => BirthdayNotificationChannel::WhatsApp->value,
            'title' => fake()->sentence(3),
            'summary' => 'No se pudo conectar con el API de mensajería.',
            'failure_code' => 'api_messaging_unreachable',
            'analyst_message' => 'No pudimos contactar al servicio de WhatsApp/SMS de TDG.',
            'resolution_steps' => "1. Revisa el estado del API en el dashboard.\n2. Reintenta el envío.",
            'sent_count' => 0,
            'total_count' => 1,
            'recipient' => '+584120000000',
            'sent_by_id' => User::factory(),
            'logged_at' => now(),
        ];
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'status' => NotificationDispatchStatus::Failed->value,
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (): array => [
            'status' => NotificationDispatchStatus::Sent->value,
            'failure_code' => null,
            'analyst_message' => 'El envío se completó sin incidencias reportadas.',
            'resolution_steps' => 'No necesitas hacer nada.',
            'sent_count' => 10,
            'total_count' => 10,
        ]);
    }
}
