<?php

namespace Database\Factories;

use App\Marketing\ContentPillar;
use App\Marketing\ContentPostBlocker;
use App\Marketing\ContentPostFormat;
use App\Marketing\ContentPostPriority;
use App\Marketing\ContentPostStatus;
use App\Models\Brand;
use App\Models\ContentPost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentPost>
 */
class ContentPostFactory extends Factory
{
    protected $model = ContentPost::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'brand_id' => Brand::factory(),
            'title' => fake()->sentence(5),
            'copy' => fake()->paragraph(),
            'status' => fake()->randomElement(ContentPostStatus::cases()),
            'priority' => fake()->randomElement(ContentPostPriority::cases()),
            'blocker' => ContentPostBlocker::None,
            'format' => fake()->randomElement(ContentPostFormat::cases()),
            'pillar' => fake()->randomElement(ContentPillar::cases()),
            'scheduled_at' => fake()->dateTimeBetween('-1 week', '+3 weeks'),
            'published_at' => null,
            'time_spent_seconds' => fake()->numberBetween(0, 7200),
            'timer_started_at' => null,
            'is_replicable' => false,
            'is_evergreen' => false,
            'reference_image' => null,
            'board_position' => 0,
            'feed_position' => null,
            'created_by_id' => null,
        ];
    }

    public function status(ContentPostStatus $status): static
    {
        return $this->state(fn (): array => ['status' => $status]);
    }

    public function blocked(ContentPostBlocker $blocker = ContentPostBlocker::MissingClientVideo): static
    {
        return $this->state(fn (): array => ['blocker' => $blocker]);
    }

    public function urgent(): static
    {
        return $this->state(fn (): array => ['priority' => ContentPostPriority::Urgent]);
    }

    public function evergreen(): static
    {
        return $this->state(fn (): array => [
            'is_evergreen' => true,
            'scheduled_at' => null,
            'status' => ContentPostStatus::Idea,
        ]);
    }

    public function replicable(): static
    {
        return $this->state(fn (): array => ['is_replicable' => true]);
    }

    public function timerRunning(): static
    {
        return $this->state(fn (): array => ['timer_started_at' => now()->subMinutes(5)]);
    }
}
