<?php

namespace Database\Factories;

use App\Enums\VolunteerArea;
use App\Enums\VolunteerAvailability;
use App\Models\VolunteerApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VolunteerApplication>
 */
class VolunteerApplicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => '+573'.fake()->numerify('#########'),
            'phone_has_whatsapp' => true,
            'email' => fake()->boolean(60) ? fake()->safeEmail() : null,
            'areas' => fake()->randomElements([VolunteerArea::Spaces, VolunteerArea::Events, VolunteerArea::Reading], 2),
            'availability' => fake()->randomElement(VolunteerAvailability::cases()),
            'message' => fake()->boolean() ? fake()->sentence(14) : null,
            'privacy_accepted_at' => now(),
            'privacy_policy_version' => config('school.privacy_policy_version'),
            'ip_address' => '127.0.0.1',
        ];
    }
}
