# RMSVS Tenant Management System - AI Copilot Instructions

## Architecture Overview

This is a **multi-tenant Laravel application** using Spatie's multitenancy package with **dual Filament panels** for property management (hotels, hostels, residential facilities). The system manages guest profiles, room assignments, QR-based scanning, meal tracking, and facility operations.

### Core Multi-tenancy Pattern
- **Landlord connection**: Manages tenants, users, and system-wide data
- **Tenant connection**: Isolated databases per tenant for guest data, rooms, scans
- **Model traits**: `UsesLandlordConnection` vs `UsesTenantConnection` determine database routing
- **Authentication guards**: `web` (landlord users) vs `tenant` (property staff)

### Dual Panel Architecture
```php
// AdminPanelProvider.php - Main tenant operations (/admin)
->discoverResources(in: app_path('Filament/Resources/Tenant'), for: 'App\\Filament\\Resources\\Tenant')
->authGuard('tenant') // UserTenant model
->middleware([NeedsTenant::class, EnsureValidTenantSession::class])

// SuperPanelProvider.php - System administration (/super)  
->discoverResources(in: app_path('Filament/Resources/Landlord'), for: 'App\\Filament\\Resources\\Landlord')
->authGuard('web') // User model
```

## Key Development Patterns

### Database Connection Strategy
```php
// Landlord models (system-wide)
class User extends Authenticatable {
    use UsesLandlordConnection;
}

// Tenant models (property-specific)
class Guest extends Model {
    use UsesTenantConnection;
}
```

### Navigation & Permissions
Resources use permission-based navigation with guard checks:
```php
public static function shouldRegisterNavigation(): bool
{
    return Auth::guard('tenant')->check() && 
           Auth::guard('tenant')->user()->can('view guest');
}
```

Navigation groups: `Property` (rooms, guests, requests), `Scans` (QR operations), `Settings` (users, permissions).

### Guest Lifecycle Management
- **Auto check-in**: Residents with `assigned_room_id` get automatic CheckIn records
- **QR generation**: Guests and rooms generate QR codes on creation via model boot methods
- **Room status**: Automatically updates to 'occupied' when residents are assigned

### QR Code Integration
Central to operations - guests, rooms, and check-ins all generate QR codes for scanning workflows:
```php
// In Guest model boot()
static::created(function ($guest) {
    $guest->generateQrCode();
    // Auto-create CheckIn for residents...
});
```

## Common Development Tasks

### Adding New Filament Resources
1. **Determine context**: Tenant vs Landlord resource location
2. **Set guard**: Use `shouldRegisterNavigation()` with appropriate guard check
3. **Add permissions**: Follow naming convention `view {model}`, `create {model}`, etc.
4. **Navigation group**: Add to Property/Scans/Settings as appropriate

### Working with Multi-tenancy
- **Migrations**: Separate `database/migrations/landlord/` and `database/migrations/tenant/`
- **Testing tenant context**: Use `Tenant::checkCurrent()` or tenant:artisan commands
- **Cross-tenant queries**: Only possible via landlord connection models

### QR Code Features
- **Regeneration command**: `php artisan qr:regenerate` for all active check-ins
- **Room-guest QR**: Generated when residents are assigned rooms
- **Scan workflows**: Check `app/Filament/Pages/Tenant/ManualScanPage` for patterns

## Environment Configuration

Key environment variables for tenant setup:
```bash
APP_DOMAIN=tenant.solennico.com  # Tenant subdomain pattern
DB_CONNECTION=landlord           # Default connection
DB_LANDLORD_*                   # Landlord database config
```

## Debugging & Commands

### Essential Artisan Commands
```bash
# Tenant operations
php artisan tenant:list
php artisan tenant:artisan {tenant_id} migrate
php artisan tenant:artisan {tenant_id} db:seed

# QR code maintenance  
php artisan qr:regenerate

# Filament
php artisan filament:upgrade
```

### Permission System
Uses Spatie Laravel Permission with tenant-aware guards. Permissions are tenant-scoped and checked in Filament resources via `HasPermissionBasedAccess` trait.

## File Organization Conventions

- **Filament Resources**: `app/Filament/Resources/{Landlord|Tenant}/`
- **Models**: Standard Laravel with connection traits
- **Migrations**: Split by `database/migrations/{landlord|tenant}/`
- **Guards**: Separate provider models (`User` vs `UserTenant`)

When adding features, always consider the landlord/tenant boundary and use appropriate connection traits and guard checks.