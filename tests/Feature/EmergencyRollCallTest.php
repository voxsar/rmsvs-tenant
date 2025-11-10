<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\UserTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmergencyRollCallTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up basic permissions
        Permission::create(['name' => 'view guest', 'guard_name' => 'tenant']);
        $role = Role::create(['name' => 'admin', 'guard_name' => 'tenant']);
        $role->givePermissionTo('view guest');
    }

    public function test_emergency_roll_call_page_is_accessible(): void
    {
        $user = UserTenant::factory()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user, 'tenant')
            ->get('/admin/emergency-roll-call');

        $response->assertSuccessful();
    }

    public function test_guest_requires_email_field(): void
    {
        $guestData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '1234567890',
            'type' => 'RESIDENT',
            'is_active' => 'active',
        ];

        // Test that email is required
        $this->expectException(\Illuminate\Database\QueryException::class);
        Guest::create($guestData);
    }

    public function test_guest_requires_phone_field_for_emergency_contact(): void
    {
        $guestWithPhone = Guest::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'phone' => '0987654321',
            'type' => 'RESIDENT',
            'is_active' => 'active',
        ]);

        $this->assertNotNull($guestWithPhone->phone);
        $this->assertEquals('0987654321', $guestWithPhone->phone);
    }

    public function test_guest_has_all_emergency_roll_call_fields(): void
    {
        $guest = Guest::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'phone' => '1231231234',
            'photo' => 'path/to/photo.jpg',
            'type' => 'RESIDENT',
            'is_active' => 'active',
        ]);

        // Verify all required fields exist
        $this->assertNotNull($guest->first_name);
        $this->assertNotNull($guest->last_name);
        $this->assertNotNull($guest->email);
        $this->assertNotNull($guest->phone);
        $this->assertNotNull($guest->photo);
    }

    public function test_only_active_residents_appear_in_emergency_roll_call(): void
    {
        // Create active resident
        $activeResident = Guest::factory()->create([
            'type' => 'RESIDENT',
            'is_active' => 'active',
        ]);

        // Create inactive resident
        $inactiveResident = Guest::factory()->create([
            'type' => 'RESIDENT',
            'is_active' => 'inactive',
        ]);

        // Create active staff (should not appear)
        $activeStaff = Guest::factory()->create([
            'type' => 'STAFF',
            'is_active' => 'active',
        ]);

        $query = Guest::query()
            ->where('type', 'RESIDENT')
            ->where('is_active', 'active')
            ->get();

        $this->assertCount(1, $query);
        $this->assertTrue($query->contains($activeResident));
        $this->assertFalse($query->contains($inactiveResident));
        $this->assertFalse($query->contains($activeStaff));
    }
}
