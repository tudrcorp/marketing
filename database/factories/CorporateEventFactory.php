<?php

namespace Database\Factories;

use App\Marketing\BirthdayNotificationAudience;
use App\Marketing\CorporateEventModality;
use App\Marketing\CorporateEventStatus;
use App\Marketing\CorporateEventType;
use App\Models\CorporateEvent;
use App\Models\User;
use App\Services\Marketing\CorporateEventRegistrationUrlService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CorporateEvent>
 */
class CorporateEventFactory extends Factory
{
    protected $model = CorporateEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('+1 week', '+3 months');

        $token = app(CorporateEventRegistrationUrlService::class)->generateToken();

        return [
            'title' => fake()->sentence(4),
            'code' => strtoupper(fake()->bothify('EVT-####')),
            'event_type' => fake()->randomElement(CorporateEventType::cases())->value,
            'modality' => fake()->randomElement(CorporateEventModality::cases())->value,
            'status' => CorporateEventStatus::Draft->value,
            'summary' => fake()->sentence(),
            'description' => fake()->paragraphs(2, true),
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify('+2 hours'),
            'venue_name' => fake()->company(),
            'venue_address' => fake()->address(),
            'virtual_url' => fake()->optional()->url(),
            'cover_image' => null,
            'attachments' => null,
            'target_audiences' => [BirthdayNotificationAudience::Collaborators->value],
            'capacity' => fake()->optional()->numberBetween(20, 200),
            'registrations_count' => 0,
            'registration_url' => app(CorporateEventRegistrationUrlService::class)->buildUrl($token),
            'registration_token' => $token,
            'registration_deadline' => null,
            'promoted_channels' => null,
            'mass_notification_id' => null,
            'created_by_id' => User::factory(),
            'published_at' => null,
            'promoted_at' => null,
            'cancelled_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => CorporateEventStatus::Published->value,
            'published_at' => now(),
        ]);
    }

    public function promoted(): static
    {
        return $this->published()->state(fn (): array => [
            'status' => CorporateEventStatus::Promoted->value,
            'promoted_at' => now(),
            'promoted_channels' => ['email'],
        ]);
    }
}
