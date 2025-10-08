<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Guest;
use App\Models\Meal;
use App\Models\Room;
use App\Models\Consumable;
use App\Models\MealRecord;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

    	User::factory()->create([
             'name' => 'Miyuru Dharmage',
             'email' => 'voxsar@gmail.com',
			 'password' => bcrypt('12345678')
		]);

		Room::insert([
			[
				"room_no" => "101",
				"building" => "B1",
				"floor" => 01,
			],
			[
				"room_no" => "102",
				"building" => "B1",
				"floor" => 01,
			],
			[
				"room_no" => "201",
				"building" => "B1",
				"floor" => 02,
			],
			[
				"room_no" => "202",
				"building" => "B1",
				"floor" => 02,
			],
			[
				"room_no" => "101",
				"building" => "B2",
				"floor" => 01,
			],
		]);

		Guest::factory(10)->create([
			'type' => 'RESIDENT',
			'is_active' => 'active',
			'qr_code' => 'https://example.com/qr-code.png',
		]);

		Meal::insert([
			[
				"range_start" => "05:00:00",
				"range_end" => "09:00:00",
				"meal_type" => "BREAKFAST",
				"week_day" => json_encode([
					"MONDAY",
					"TUESDAY",
					"WEDNESDAY",
					"THURSDAY",
					"FRIDAY",
				]),
			],
			[
				"range_start" => "06:00:00",
				"range_end" => "10:00:00",
				"meal_type" => "BREAKFAST",
				"week_day" => json_encode([
					"SATURDAY",
					"SUNDAY",
				]),
			],
			[
				"range_start" => "11:00:00",
				"range_end" => "14:00:00",
				"meal_type" => "LUNCH",
				"week_day" => json_encode([
					"MONDAY",
					"TUESDAY",
					"WEDNESDAY",
					"THURSDAY",
					"FRIDAY",
				]),
			],
			[
				"range_start" => "11:30:00",
				"range_end" => "14:30:00",
				"meal_type" => "LUNCH",
				"week_day" => json_encode([
					"SATURDAY",
					"SUNDAY",
				]),
			],
			[
				"range_start" => "17:00:00",
				"range_end" => "20:00:00",
				"meal_type" => "DINNER",
				"week_day" => json_encode([
					"MONDAY",
					"TUESDAY",
					"WEDNESDAY",
					"THURSDAY",
					"FRIDAY",
				]),
			],
			[
				"range_start" => "18:00:00",
				"range_end" => "21:00:00",
				"meal_type" => "DINNER",
				"week_day" => json_encode([
					"SATURDAY",
					"SUNDAY",
				]),
			],
		]);

		Consumable::insert([
			[
                'name' => 'Late Dinner',
                'description' => 'Late dinner request by guest',
                'price' => 0,
                'is_visible' => false,
            ],
			[
				"name" => "Coke",
				"description" => "Coke 500ml",
				"price" => 150.00,
			],
			[
				"name" => "Toothpaste",
				"description" => "Toothpaste 100g",
				"price" => 200.00,
			],
			[
				"name" => "Shampoo",
				"description" => "Shampoo 200ml",
				"price" => 300.00,
			],
			[
				"name" => "Soap",
				"description" => "Soap 100g",
				"price" => 50.00,
			],
			[
				"name" => "Towel",
				"description" => "Towel Large",
				"price" => 500.00,
			],
			[
				"name" => "Toothbrush",
				"description" => "Toothbrush Soft",
				"price" => 100.00,
			],
			[
				"name" => "Shaving Kit",
				"description" => "Shaving Kit Disposable",
				"price" => 250.00,
			],
			[
				"name" => "Chips",
				"description" => "Chips 50g",
				"price" => 75.00,
			],
			[
				"name" => "Chocolate Bar",
				"description" => "Chocolate Bar 100g",
				"price" => 120.00,
			],
			[
				"name" => "Energy Drink",
				"description" => "Energy Drink 250ml",
				"price" => 200.00,
			],
		 ]);
    }
}
