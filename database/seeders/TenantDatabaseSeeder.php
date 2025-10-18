<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Consumable;
use App\Models\Guest;
use App\Models\Meal;
use App\Models\ModelHasRole;
use App\Models\Permission;
use App\Models\Role;
use App\Models\ScanItem;
use App\Models\Tenant;
use App\Models\UserTenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Exceptions\PermissionAlreadyExists;

class TenantDatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        $this->runTenantSpecificSeeders();
    }

    public function runTenantSpecificSeeders()
    {
        // run tenant specific seeders

        // Create permissions first
        $this->createPermissions();

        // Create roles with permissions
        $this->createRoles();

        Guest::factory(10)->create([
            'type' => 'RESIDENT',
            'is_active' => 'active',
            'qr_code' => 'https://example.com/qr-code.png',
        ]);

        UserTenant::create([
            'name' => 'Tenant Admin',
            'email' => 'admin@solennico.com',
            'password' => bcrypt('12345678'),
        ]);

        ModelHasRole::create([
            'role_id' => Role::where('name', 'Manager')->first()->id,
            'model_type' => "App\Models\UserTenant",
            'model_id' => UserTenant::where('email', 'admin@solennico.com')->first()->id,
        ]);

        Meal::insert([
            [
                'range_start' => '05:00:00',
                'range_end' => '09:00:00',
                'meal_type' => 'BREAKFAST',
                'week_day' => json_encode([
                    'MONDAY',
                    'TUESDAY',
                    'WEDNESDAY',
                    'THURSDAY',
                    'FRIDAY',
                ]),
            ],
            [
                'range_start' => '06:00:00',
                'range_end' => '10:00:00',
                'meal_type' => 'BREAKFAST',
                'week_day' => json_encode([
                    'SATURDAY',
                    'SUNDAY',
                ]),
            ],
            [
                'range_start' => '11:00:00',
                'range_end' => '14:00:00',
                'meal_type' => 'LUNCH',
                'week_day' => json_encode([
                    'MONDAY',
                    'TUESDAY',
                    'WEDNESDAY',
                    'THURSDAY',
                    'FRIDAY',
                ]),
            ],
            [
                'range_start' => '11:30:00',
                'range_end' => '14:30:00',
                'meal_type' => 'LUNCH',
                'week_day' => json_encode([
                    'SATURDAY',
                    'SUNDAY',
                ]),
            ],
            [
                'range_start' => '17:00:00',
                'range_end' => '20:00:00',
                'meal_type' => 'DINNER',
                'week_day' => json_encode([
                    'MONDAY',
                    'TUESDAY',
                    'WEDNESDAY',
                    'THURSDAY',
                    'FRIDAY',
                ]),
            ],
            [
                'range_start' => '18:00:00',
                'range_end' => '21:00:00',
                'meal_type' => 'DINNER',
                'week_day' => json_encode([
                    'SATURDAY',
                    'SUNDAY',
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
                'name' => 'Coke',
                'description' => 'Coke 500ml',
                'price' => 150.00,
                'is_visible' => false,
            ],
            [
                'name' => 'Toothpaste',
                'description' => 'Toothpaste 100g',
                'price' => 200.00,
                'is_visible' => false,
            ],
            [
                'name' => 'Shampoo',
                'description' => 'Shampoo 200ml',
                'price' => 300.00,
                'is_visible' => false,
            ],
            [
                'name' => 'Soap',
                'description' => 'Soap 100g',
                'price' => 50.00,
                'is_visible' => false,
            ],
            [
                'name' => 'Towel',
                'description' => 'Towel Large',
                'price' => 500.00,
                'is_visible' => false,
            ],
            [
                'name' => 'Toothbrush',
                'description' => 'Toothbrush Soft',
                'price' => 100.00,
                'is_visible' => false,
            ],
            [
                'name' => 'Shaving Kit',
                'description' => 'Shaving Kit Disposable',
                'price' => 250.00,
                'is_visible' => false,
            ],
            [
                'name' => 'Chips',
                'description' => 'Chips 50g',
                'price' => 75.00,
                'is_visible' => false,
            ],
            [
                'name' => 'Chocolate Bar',
                'description' => 'Chocolate Bar 100g',
                'price' => 120.00,
                'is_visible' => false,
            ],
            [
                'name' => 'Energy Drink',
                'description' => 'Energy Drink 250ml',
                'price' => 200.00,
                'is_visible' => false,
            ],
        ]);

        ScanItem::insert([
            [
                'name' => 'Main Entrance',
                'type' => ScanItem::TYPE_ACCESS,
                'description' => 'Primary access scanner for the building',
                'is_active' => true,
                'active_period_type' => ScanItem::PERIOD_ALWAYS,
                'active_days' => null,
                'notify_if_missed' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Daily Breakfast',
                'type' => ScanItem::TYPE_MEAL,
                'description' => 'Standard breakfast service window',
                'is_active' => true,
                'active_period_type' => ScanItem::PERIOD_WEEKDAYS,
                'active_days' => json_encode(['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY']),
                'active_start_time' => '06:00:00',
                'active_end_time' => '09:00:00',
                'notify_if_missed' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Wellness Pack',
                'type' => ScanItem::TYPE_CONSUMABLE,
                'description' => 'Daily amenity pack distribution',
                'is_active' => true,
                'active_period_type' => ScanItem::PERIOD_CUSTOM,
                'custom_windows' => json_encode([
                    [
                        'days' => ['MONDAY', 'WEDNESDAY', 'FRIDAY'],
                        'start' => '10:00:00',
                        'end' => '12:00:00',
                    ],
                ]),
                'notify_if_missed' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

    }

    /**
     * Create all permissions for the application
     */
    private function createPermissions()
    {
        // Define resources that need permissions
        $resources = [
            'user', 'role', 'permission', 'guest', 'room', 'meal',
            'meal-record', 'consumable', 'check-in', 'guest-request',
            'daily-report', 'scanner', 'transit', 'scan-item',
        ];

        // Define standard CRUD operations
        $operations = ['view', 'create', 'update', 'delete', 'manage'];

        // Create permissions for each resource and operation
        foreach ($resources as $resource) {
            foreach ($operations as $operation) {
                try {
                    Permission::create([
                        'name' => "$operation $resource",
                        'guard_name' => 'tenant',
                    ]);
                } catch (PermissionAlreadyExists $e) {
                    Log::info($e->getMessage());
                    // Handle the exception if needed
                }
            }
        }

        // Additional specialized permissions
        $additionalPermissions = [
            'assign roles',
            'assign permissions',
            'access admin panel',
            'view dashboard',
            'generate reports',
            'export data',
            'access system settings',
            'manage tenant settings',
            'view audit logs',
            'view activity logs',
            'approve guest requests',
            'reject guest requests',
            'process check-ins',
            'process check-outs',
            'scan meal qr-codes',
            'override meal limits',
            'manage consumable inventory',
        ];

        foreach ($additionalPermissions as $permission) {
            Permission::create([
                'name' => $permission,
                'guard_name' => 'tenant',
            ]);
        }
    }

    /**
     * Create roles with appropriate permissions
     */
    private function createRoles()
    {
        // Manager role - Can manage users, rooms, and approve requests
        $manager = Role::create([
            'name' => 'Manager',
            'guard_name' => 'tenant',
        ]);

        // Manager permissions - Full access to most features
        $managerPermissions = [
            'view user', 'create user', 'update user', 'delete user',
            'view guest', 'create guest', 'update guest', 'delete guest',
            'view room', 'create room', 'update room', 'delete room',
            'view meal', 'create meal', 'update meal', 'delete meal',
            'view meal-record', 'create meal-record', 'update meal-record',
            'view consumable', 'create consumable', 'update consumable', 'delete consumable',
            'view check-in', 'create check-in', 'update check-in', 'delete check-in',
            'view guest-request', 'create guest-request', 'update guest-request', 'delete guest-request',
            'view daily-report', 'create daily-report', 'update daily-report',
            'view scanner', 'create scanner', 'update scanner', 'delete scanner',
            'view scan-item', 'create scan-item', 'update scan-item', 'delete scan-item',
            'view transit', 'create transit', 'update transit', 'delete transit',
            'access admin panel', 'view dashboard',
            'generate reports', 'export data',
            'approve guest requests', 'reject guest requests',
            'process check-ins', 'process check-outs',
            'scan meal qr-codes', 'override meal limits',
            'manage consumable inventory',
            'assign roles',
        ];
        $manager->givePermissionTo($managerPermissions);

        // Senior role - Can handle most operations but cannot manage users or approve all requests
        $senior = Role::create([
            'name' => 'Senior',
            'guard_name' => 'tenant',
        ]);

        // Senior permissions
        $seniorPermissions = [
            'view user', 'view guest', 'create guest', 'update guest',
            'view room', 'create room', 'update room',
            'view meal', 'view meal-record', 'create meal-record', 'update meal-record',
            'view consumable', 'update consumable',
            'view check-in', 'create check-in', 'update check-in',
            'view guest-request', 'create guest-request', 'update guest-request',
            'view daily-report', 'create daily-report',
            'view scanner', 'view transit', 'create transit', 'update transit',
            'view scan-item', 'update scan-item',
            'access admin panel', 'view dashboard',
            'process check-ins', 'process check-outs',
            'scan meal qr-codes',
            'approve guest requests', // Can approve basic requests
        ];
        $senior->givePermissionTo($seniorPermissions);

        // Junior role - Basic operations and data entry
        $junior = Role::create([
            'name' => 'Junior',
            'guard_name' => 'tenant',
        ]);

        // Junior permissions
        $juniorPermissions = [
            'view guest', 'create guest', 'update guest',
            'view room', 'view meal', 'view meal-record', 'create meal-record',
            'view consumable', 'view check-in', 'create check-in',
            'view guest-request', 'create guest-request',
            'view transit', 'create transit',
            'access admin panel', 'view dashboard',
            'process check-ins', 'scan meal qr-codes',
        ];
        $junior->givePermissionTo($juniorPermissions);

        // Scanner role - Limited to scanning and meal recording
        $scanner = Role::create([
            'name' => 'Scanner',
            'guard_name' => 'tenant',
        ]);

        // Scanner permissions - Very limited, mainly for QR code scanning
        $scannerPermissions = [
            'view guest', 'view meal', 'view meal-record', 'create meal-record',
            'scan meal qr-codes', 'access admin panel',
        ];
        $scanner->givePermissionTo($scannerPermissions);
    }
}
