<?php

namespace Database\Factories;

use App\Enums\GuardianRelationship;
use App\Http\Requests\StoreAdmissionRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\AdmissionRequest>
 */
class AdmissionRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'school_year' => config('school.admissions.school_year'),
            'grade' => fake()->randomElement(StoreAdmissionRequest::grades()),
            'student_first_names' => fake()->firstName(),
            'student_last_names' => fake()->lastName().' '.fake()->lastName(),
            'student_birth_date' => fake()->dateTimeBetween('-15 years', '-3 years'),
            'current_school' => fake()->optional()->randomElement(['Institución Educativa Técnica Agropecuaria', 'Jardín Infantil Pasitos', 'Colegio Nuestra Señora']),
            'guardian_name' => fake()->name(),
            'guardian_relationship' => fake()->randomElement(GuardianRelationship::cases()),
            'guardian_phone' => '+573'.fake()->numerify('#########'),
            'phone_has_whatsapp' => true,
            'guardian_email' => fake()->optional()->safeEmail(),
            'comments' => null,
            'privacy_accepted_at' => now(),
            'privacy_policy_version' => config('school.privacy_policy_version'),
            'ip_address' => fake()->ipv4(),
        ];
    }
}
