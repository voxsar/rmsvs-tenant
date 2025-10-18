<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Guest>
 */
class GuestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // create columns for the guest table
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'middle_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),

            'TRN' => $this->faker->unique()->numerify('TRN-########'),
            'date_of_birth' => $this->faker->dateTimeBetween('-50 years', '-18 years'),
            'nationality' => $this->faker->country(),
            'marital_status' => $this->faker->randomElement(['SINGLE', 'DIVORCED', 'MARRIED']),
            'age_type' => $this->faker->randomElement(['ADULT', 'CHILD', 'SENIOR']),
            'medical_history' => $this->faker->sentence(),
            'sex' => $this->faker->randomElement(['male', 'female', 'other']),
            'photo' => $this->faker->imageUrl(640, 480, 'people'),
            'is_active' => $this->faker->randomElement(['active', 'inactive']),
            'qr_code' => $this->faker->imageUrl(640, 480, 'abstract'),
            'type' => $this->faker->randomElement(['RESIDENT', 'STAFF', 'VISITORS']),
        ];
    }
}
