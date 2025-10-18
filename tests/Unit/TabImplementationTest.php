<?php

namespace Tests\Unit;

use App\Filament\Resources\Tenant\GuestResource\Pages\ListGuests;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class TabImplementationTest extends TestCase
{
    /**
     * Test that ListGuests page has getTabs method.
     */
    public function test_list_guests_has_get_tabs_method(): void
    {
        $this->assertTrue(
            method_exists(ListGuests::class, 'getTabs'),
            'ListGuests should have getTabs method'
        );
    }

    /**
     * Test that getTabs returns an array with proper structure.
     */
    public function test_get_tabs_returns_array(): void
    {
        // Create a mock of ListGuests to test the getTabs method
        $reflection = new ReflectionClass(ListGuests::class);
        $method = $reflection->getMethod('getTabs');
        $method->setAccessible(true);

        // We can't easily instantiate the page without Filament setup,
        // but we can verify the method signature
        $this->assertTrue(
            $method->isPublic(),
            'getTabs method should be public'
        );

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType, 'getTabs should have a return type');
        $this->assertEquals('array', $returnType->getName(), 'getTabs should return an array');
    }

    /**
     * Test that the getTabs method contains expected tab keys by analyzing the code.
     */
    public function test_tabs_configuration_structure(): void
    {
        // Read the file content to verify tab configuration
        $filePath = __DIR__.'/../../app/Filament/Resources/Tenant/GuestResource/Pages/ListGuests.php';
        $content = file_get_contents($filePath);

        // Check that 'active' tab is defined
        $this->assertStringContainsString(
            "'active' => Tab::make('Active')",
            $content,
            'ListGuests should define an active tab'
        );

        // Check that 'inactive' tab is defined
        $this->assertStringContainsString(
            "'inactive' => Tab::make('InActive')",
            $content,
            'ListGuests should define an inactive tab'
        );

        // Check that active tab has badge
        $this->assertStringContainsString(
            "->badge('Active')",
            $content,
            'Active tab should have a badge'
        );

        // Check that tabs use modifyQueryUsing
        $this->assertStringContainsString(
            '->modifyQueryUsing',
            $content,
            'Tabs should use modifyQueryUsing for filtering'
        );

        // Check that active tab filters by is_active = 'active'
        $this->assertStringContainsString(
            "->where('is_active', 'active')",
            $content,
            'Active tab should filter by is_active = active'
        );

        // Check that inactive tab filters by is_active = 'inactive'
        $this->assertStringContainsString(
            "->where('is_active', 'inactive')",
            $content,
            'Inactive tab should filter by is_active = inactive'
        );
    }

    /**
     * Test that ListGuests properly imports Tab class.
     */
    public function test_list_guests_imports_tab_class(): void
    {
        $filePath = __DIR__.'/../../app/Filament/Resources/Tenant/GuestResource/Pages/ListGuests.php';
        $content = file_get_contents($filePath);

        $this->assertStringContainsString(
            'use Filament\Resources\Components\Tab;',
            $content,
            'ListGuests should import Tab class'
        );
    }

    /**
     * Test that ListGuests extends ListRecords.
     */
    public function test_list_guests_extends_list_records(): void
    {
        $reflection = new ReflectionClass(ListGuests::class);
        $parentClass = $reflection->getParentClass();

        $this->assertNotFalse($parentClass, 'ListGuests should have a parent class');
        $this->assertEquals(
            'Filament\Resources\Pages\ListRecords',
            $parentClass->getName(),
            'ListGuests should extend ListRecords'
        );
    }
}
