<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Guest;
use App\Models\Meal;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\Consumable;
use App\Models\MealRecord;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        $this->runLandlordSpecificSeeders();
    }


    public function runLandlordSpecificSeeders()
    {
        // run landlord specific seeders

    	User::factory()->create([
			'name' => 'Miyuru Dharmage',
			'email' => 'voxsar@gmail.com',
			'password' => bcrypt('12345678')
	   ]);

	   Tenant::insert([
			[
				'name'=> 'landlord',
				'domain'=> env('APP_DOMAIN'),
				'database'=> 'tenant_db',
			]
		]);
    }
}
