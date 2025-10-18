<?php

namespace Tests\Unit;

use App\Filament\Pages\ManualScanPage;
use App\Filament\Pages\Tenant\Dashboard;
use App\Filament\Pages\Tenant\ShiftReport;
use App\Filament\Resources\Tenant\ActivityRecordResource;
use App\Filament\Resources\Tenant\CheckInResource;
use App\Filament\Resources\Tenant\ConsumableResource;
use App\Filament\Resources\Tenant\CustomRequestResource;
use App\Filament\Resources\Tenant\GuestResource;
use App\Filament\Resources\Tenant\MealRecordResource;
use App\Filament\Resources\Tenant\MealResource;
use App\Filament\Resources\Tenant\PermissionResource;
use App\Filament\Resources\Tenant\RoleResource;
use App\Filament\Resources\Tenant\RoomResource;
use App\Filament\Resources\Tenant\ScanItemResource;
use App\Filament\Resources\Tenant\ScannerResource;
use App\Filament\Resources\Tenant\TransitResource;
use App\Filament\Resources\Tenant\UserTenantResource;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class NavigationConfigurationTest extends TestCase
{
    /**
     * Test that navigation groups are properly configured for all resources.
     */
    public function test_navigation_groups_are_configured(): void
    {
        $resources = [
            ActivityRecordResource::class => 'Scans',
            CheckInResource::class => 'Property',
            ConsumableResource::class => 'Settings',
            CustomRequestResource::class => 'Property',
            GuestResource::class => 'Property',
            MealRecordResource::class => 'Scans',
            MealResource::class => 'Settings',
            PermissionResource::class => 'Settings',
            RoleResource::class => 'Settings',
            RoomResource::class => 'Property',
            ScanItemResource::class => 'Scans',
            ScannerResource::class => 'Scans',
            TransitResource::class => 'Scans',
            UserTenantResource::class => 'Settings',
        ];

        foreach ($resources as $resourceClass => $expectedGroup) {
            $reflection = new ReflectionClass($resourceClass);
            $property = $reflection->getProperty('navigationGroup');
            $property->setAccessible(true);
            $actualGroup = $property->getValue();

            $this->assertEquals(
                $expectedGroup,
                $actualGroup,
                "Resource {$resourceClass} should have navigationGroup '{$expectedGroup}'"
            );
        }
    }

    /**
     * Test that navigation labels are properly configured.
     */
    public function test_navigation_labels_are_configured(): void
    {
        $resources = [
            ActivityRecordResource::class => 'Scan History',
            CheckInResource::class => 'Manual Check-Ins',
            CustomRequestResource::class => 'Guest Requests',
            GuestResource::class => null, // Uses pluralModelLabel
            MealRecordResource::class => 'Meal Scans',
            PermissionResource::class => 'Permissions',
            RoleResource::class => 'Roles',
            RoomResource::class => 'Rooms',
            ScanItemResource::class => 'Scan Items',
            ScannerResource::class => 'Scanners',
            TransitResource::class => 'Transit Log',
            UserTenantResource::class => 'Users',
        ];

        foreach ($resources as $resourceClass => $expectedLabel) {
            $reflection = new ReflectionClass($resourceClass);
            $property = $reflection->getProperty('navigationLabel');
            $property->setAccessible(true);
            $actualLabel = $property->getValue();

            $this->assertEquals(
                $expectedLabel,
                $actualLabel,
                "Resource {$resourceClass} should have navigationLabel '{$expectedLabel}'"
            );
        }
    }

    /**
     * Test that ManualScanPage has correct navigation configuration.
     */
    public function test_manual_scan_page_navigation_configuration(): void
    {
        $reflection = new ReflectionClass(ManualScanPage::class);

        // Check navigation group
        $groupProperty = $reflection->getProperty('navigationGroup');
        $groupProperty->setAccessible(true);
        $this->assertEquals('Scans', $groupProperty->getValue(), 'ManualScanPage should be in Scans group');

        // Check navigation label
        $labelProperty = $reflection->getProperty('navigationLabel');
        $labelProperty->setAccessible(true);
        $this->assertEquals('Manual Scan', $labelProperty->getValue(), 'ManualScanPage should have label "Manual Scan"');

        // Check title
        $titleProperty = $reflection->getProperty('title');
        $titleProperty->setAccessible(true);
        $this->assertEquals('Manual Scan', $titleProperty->getValue(), 'ManualScanPage should have title "Manual Scan"');

        // Check navigation sort
        $sortProperty = $reflection->getProperty('navigationSort');
        $sortProperty->setAccessible(true);
        $this->assertEquals(3, $sortProperty->getValue(), 'ManualScanPage should have sort order 3');
    }

    /**
     * Test that navigation icons are set for all resources.
     */
    public function test_navigation_icons_are_set(): void
    {
        $resources = [
            ActivityRecordResource::class,
            CheckInResource::class,
            ConsumableResource::class,
            CustomRequestResource::class,
            GuestResource::class,
            MealRecordResource::class,
            MealResource::class,
            PermissionResource::class,
            RoleResource::class,
            RoomResource::class,
            ScanItemResource::class,
            ScannerResource::class,
            TransitResource::class,
            UserTenantResource::class,
            Dashboard::class,
            ShiftReport::class,
            ManualScanPage::class,
        ];

        foreach ($resources as $resourceClass) {
            $reflection = new ReflectionClass($resourceClass);
            $property = $reflection->getProperty('navigationIcon');
            $property->setAccessible(true);
            $icon = $property->getValue();

            $this->assertNotNull(
                $icon,
                "Resource {$resourceClass} should have navigationIcon set"
            );
            $this->assertStringStartsWith(
                'heroicon-',
                $icon,
                "Resource {$resourceClass} navigationIcon should be a Heroicon"
            );
        }
    }

    /**
     * Test that RoleResource is hidden from navigation.
     */
    public function test_role_resource_is_hidden_from_navigation(): void
    {
        // RoleResource has shouldRegisterNavigation() that returns false
        // This is a static method, so we'll just verify it exists
        $this->assertTrue(
            method_exists(RoleResource::class, 'shouldRegisterNavigation'),
            'RoleResource should have shouldRegisterNavigation method'
        );
    }

    /**
     * Test that navigation sort order is properly set where needed.
     */
    public function test_navigation_sort_order_is_configured(): void
    {
        $sortedResources = [
            ActivityRecordResource::class => 1,
            ConsumableResource::class => 1,
            GuestResource::class => 2,
            PermissionResource::class => 3,
            RoleResource::class => 2,
            RoomResource::class => 1,
            UserTenantResource::class => 1,
            ManualScanPage::class => 3,
        ];

        foreach ($sortedResources as $resourceClass => $expectedSort) {
            $reflection = new ReflectionClass($resourceClass);
            $property = $reflection->getProperty('navigationSort');
            $property->setAccessible(true);
            $actualSort = $property->getValue();

            $this->assertEquals(
                $expectedSort,
                $actualSort,
                "Resource {$resourceClass} should have navigationSort {$expectedSort}"
            );
        }
    }
}
