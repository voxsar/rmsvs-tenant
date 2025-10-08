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
            //create columns for the guest table
			'first_name' => $this->faker->firstName(),
			'last_name' => $this->faker->lastName(),
			'middle_name' => $this->faker->lastName(),
			'email' => $this->faker->unique()->safeEmail(),
			'phone' => $this->faker->phoneNumber(),

			'TRN' => $this->faker->unique()->numerify('TRN-########'),
			'date_of_birth' => $this->faker->dateTimeBetween('-50 years', '-18 years'),
			/*
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('trn')->nullable();
            $table->string('nationality')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('age_type')->nullable();
            $table->string('medical_history')->nullable();
            $table->enum('sex', ['male', 'female', 'other'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('photo')->nullable();
            $table->enum('is_active', ['active', 'inactive'])->default('active');
            $table->string('qr_code')->nullable();
            $table->enum('type', ['RESIDENT', 'STAFF', 'VISITORS'])->default('RESIDENT');*/

			'nationality' => $this->faker->country(),
			'marital_status' => $this->faker->randomElement(['single', 'married', 'divorced']),
			'age_type' => $this->faker->randomElement(['adult', 'child']),
			'medical_history' => $this->faker->sentence(),
			'sex' => $this->faker->randomElement(['male', 'female', 'other']),
			'photo' => $this->faker->imageUrl(640, 480, 'people'),
			'is_active' => $this->faker->randomElement(['active', 'inactive']),
			'qr_code' => $this->faker->imageUrl(640, 480, 'abstract'),
			'type' => $this->faker->randomElement(['RESIDENT', 'STAFF', 'VISITORS']),
        ];
    }
}
