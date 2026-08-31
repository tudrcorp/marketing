<?php

namespace Database\Factories;

use App\Marketing\PublicationStatus;
use App\Models\EditorialPublication;
use App\Models\SocialAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EditorialPublication>
 */
class EditorialPublicationFactory extends Factory
{
    protected $model = EditorialPublication::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'social_account_id' => SocialAccount::factory(),
            'title' => fake()->sentence(6),
            'body' => fake()->paragraphs(2, true),
            'reference_image' => null,
            'media_urls' => null,
            'hashtags' => ['#TuDoctorGroup', '#Salud'],
            'scheduled_at' => fake()->dateTimeBetween('now', '+2 weeks'),
            'published_at' => null,
            'status' => PublicationStatus::Draft,
            'approval_notes' => null,
            'approved_by_id' => null,
            'approved_at' => null,
            'created_by_id' => null,
        ];
    }

    public function scheduled(): static
    {
        return $this->state(fn (): array => [
            'status' => PublicationStatus::Scheduled,
            'approved_at' => now(),
        ]);
    }

    public function pendingApproval(): static
    {
        return $this->state(fn (): array => [
            'status' => PublicationStatus::PendingApproval,
        ]);
    }
}
