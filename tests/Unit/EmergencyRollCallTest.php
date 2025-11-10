<?php

namespace Tests\Unit;

use App\Filament\Pages\Tenant\EmergencyRollCall;
use App\Filament\Resources\Tenant\GuestResource;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class EmergencyRollCallTest extends TestCase
{
    /**
     * Test that EmergencyRollCall page exists and is configured properly
     */
    public function test_emergency_roll_call_page_exists(): void
    {
        $this->assertTrue(class_exists(EmergencyRollCall::class));
    }

    /**
     * Test that EmergencyRollCall page has correct navigation configuration
     */
    public function test_emergency_roll_call_navigation_is_configured(): void
    {
        $reflection = new ReflectionClass(EmergencyRollCall::class);

        // Check navigation icon
        $navigationIcon = $reflection->getProperty('navigationIcon');
        $navigationIcon->setAccessible(true);
        $this->assertNotNull($navigationIcon->getValue());

        // Check navigation group
        $navigationGroup = $reflection->getProperty('navigationGroup');
        $navigationGroup->setAccessible(true);
        $this->assertEquals('Property', $navigationGroup->getValue());

        // Check navigation label
        $navigationLabel = $reflection->getProperty('navigationLabel');
        $navigationLabel->setAccessible(true);
        $this->assertEquals('Emergency Roll Call', $navigationLabel->getValue());
    }

    /**
     * Test that EmergencyRollCall has required methods
     */
    public function test_emergency_roll_call_has_required_methods(): void
    {
        $reflection = new ReflectionClass(EmergencyRollCall::class);

        $this->assertTrue($reflection->hasMethod('table'));
        $this->assertTrue($reflection->hasMethod('getTitle'));
        $this->assertTrue($reflection->hasMethod('shouldRegisterNavigation'));
    }

    /**
     * Test that GuestResource form requires email field
     */
    public function test_guest_resource_form_includes_email_field(): void
    {
        $reflection = new ReflectionClass(GuestResource::class);
        $source = file_get_contents($reflection->getFileName());
        
        // Check that email field exists and is required
        $this->assertStringContainsString("Forms\Components\TextInput::make('email')", $source);
        
        // Find the email field section and verify it has required()
        $emailPos = strpos($source, "Forms\Components\TextInput::make('email')");
        $this->assertNotFalse($emailPos);
        
        // Check for ->required() after email field (within next 200 chars)
        $emailSection = substr($source, $emailPos, 200);
        $this->assertStringContainsString('->required()', $emailSection);
    }

    /**
     * Test that GuestResource form includes phone field
     */
    public function test_guest_resource_form_includes_phone_field(): void
    {
        $reflection = new ReflectionClass(GuestResource::class);
        $source = file_get_contents($reflection->getFileName());
        
        // Check that phone field exists and is required
        $this->assertStringContainsString("Forms\Components\TextInput::make('phone')", $source);
        
        // Find the phone field section and verify it has required()
        $phonePos = strpos($source, "Forms\Components\TextInput::make('phone')");
        $this->assertNotFalse($phonePos);
        
        // Check for ->required() after phone field (within next 200 chars)
        $phoneSection = substr($source, $phonePos, 200);
        $this->assertStringContainsString('->required()', $phoneSection);
    }

    /**
     * Test that EmergencyRollCall table has all required columns
     */
    public function test_emergency_roll_call_table_has_required_columns(): void
    {
        $reflection = new ReflectionClass(EmergencyRollCall::class);
        $source = file_get_contents($reflection->getFileName());
        
        // Verify all required columns are present
        $this->assertStringContainsString("Tables\Columns\TextColumn::make('full_name')", $source);
        $this->assertStringContainsString("Tables\Columns\TextColumn::make('phone')", $source);
        $this->assertStringContainsString("Tables\Columns\TextColumn::make('email')", $source);
        $this->assertStringContainsString("Tables\Columns\TextColumn::make('last_scan')", $source);
        $this->assertStringContainsString("Tables\Columns\TextColumn::make('room')", $source);
        $this->assertStringContainsString("Tables\Columns\TextColumn::make('status')", $source);
    }

    /**
     * Test that EmergencyRollCall has view photo action
     */
    public function test_emergency_roll_call_has_view_photo_action(): void
    {
        $reflection = new ReflectionClass(EmergencyRollCall::class);
        $source = file_get_contents($reflection->getFileName());
        
        // Verify view photo action exists
        $this->assertStringContainsString("Tables\Actions\Action::make('view_photo')", $source);
        $this->assertStringContainsString("'View Photo'", $source);
    }

    /**
     * Test that EmergencyRollCall filters only active residents
     */
    public function test_emergency_roll_call_filters_active_residents(): void
    {
        $reflection = new ReflectionClass(EmergencyRollCall::class);
        $source = file_get_contents($reflection->getFileName());
        
        // Verify query filters for RESIDENT type and active status
        $this->assertStringContainsString("->where('type', 'RESIDENT')", $source);
        $this->assertStringContainsString("->where('is_active', 'active')", $source);
    }

    /**
     * Test that EmergencyRollCall view file exists
     */
    public function test_emergency_roll_call_view_file_exists(): void
    {
        $basePath = dirname(__DIR__, 2);
        $viewPath = $basePath . '/resources/views/filament/pages/tenant/emergency-roll-call.blade.php';
        $this->assertFileExists($viewPath);
    }

    /**
     * Test that guest photo modal component exists
     */
    public function test_guest_photo_modal_component_exists(): void
    {
        $basePath = dirname(__DIR__, 2);
        $componentPath = $basePath . '/resources/views/filament/components/guest-photo-modal.blade.php';
        $this->assertFileExists($componentPath);
    }
}
