<?php

namespace App\Filament\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait HasPermissionBasedAccess
{
    /**
     * Base resource name used for permissions
     * Override this in resources to use a different permission base
     */
    protected static function getPermissionBase(): string
    {
        // Extract the resource name from the class name by default
        // Example: App\Filament\Resources\Tenant\GuestResource -> guest
        $resourceName = static::getModelLabel();
        // replace space with underscore
        $resourceName = str_replace(' ', '-', $resourceName);

        // Convert to lowercase
        return strtolower($resourceName);
    }

    /**
     * Check if the authenticated user can view this resource
     */
    public static function canAccess(): bool
    {
        // Hidden if no tenant session or no authenticated user
        if (! Auth::guard('tenant')->check()) {
            return false;
        }

        // Get permission name from permission base
        $permissionName = 'view '.static::getPermissionBase();

        return Auth::guard('tenant')->user()->can($permissionName);
    }

    /**
     * Check if the user can create this resource
     */
    public static function canCreate(): bool
    {
        if (! Auth::guard('tenant')->check()) {
            return false;
        }

        $permissionName = 'create '.static::getPermissionBase();

        return Auth::guard('tenant')->user()->can($permissionName);
    }

    /**
     * Check if the user can edit a resource
     */
    public static function canEdit(Model $record): bool
    {
        if (! Auth::guard('tenant')->check()) {
            return false;
        }

        $permissionName = 'update '.static::getPermissionBase();

        return Auth::guard('tenant')->user()->can($permissionName);
    }

    /**
     * Check if the user can delete this resource
     */
    public static function canDelete(Model $record): bool
    {
        if (! Auth::guard('tenant')->check()) {
            return false;
        }

        $permissionName = 'delete '.static::getPermissionBase();

        return Auth::guard('tenant')->user()->can($permissionName);
    }

    /**
     * Check if the user can delete any resources (for bulk actions)
     */
    public static function canDeleteAny(): bool
    {
        if (! Auth::guard('tenant')->check()) {
            return false;
        }

        $permissionName = 'delete '.static::getPermissionBase();

        return Auth::guard('tenant')->user()->can($permissionName);
    }

    /**
     * Check if the user can view any resources
     */
    public static function canViewAny(): bool
    {
        if (! Auth::guard('tenant')->check()) {
            return false;
        }

        $permissionName = 'view '.static::getPermissionBase();

        return Auth::guard('tenant')->user()->can($permissionName);
    }
}
