<?php

namespace Database\Factories;

use App\Marketing\SocialPlatform;
use App\Models\SocialAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SocialAccount>
 */
class SocialAccountFactory extends Factory
{
    protected $model = SocialAccount::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $platform = fake()->randomElement(SocialPlatform::cases());
        $handle = fake()->userName();

        return [
            'name' => 'TDG '. $platform->getLabel(),
            'platform' => $platform,
            'handle' => '@'.$handle,
            'profile_url' => 'https://example.com/'.$handle,
            'is_active' => true,
            'notes' => null,
            'created_by_id' => null,
        ];
    }
}
