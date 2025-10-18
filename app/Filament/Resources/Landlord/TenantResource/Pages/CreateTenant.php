<?php

namespace App\Filament\Resources\Landlord\TenantResource\Pages;

use App\Filament\Resources\Landlord\TenantResource;
use App\Models\UserTenant;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    /**
     * @var array<string, mixed>
     */
    protected array $adminCredentials = [];

    protected array $initialRoomDefinitions = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->initialRoomDefinitions = collect($data['initial_rooms'] ?? request()->input('data.initial_rooms', []))
            ->filter(fn ($room) => filled($room['room_no'] ?? null))
            ->map(fn ($room) => [
                'room_no' => $room['room_no'],
                'building' => $room['building'] ?? null,
                'floor' => $room['floor'] ?? null,
                'status' => $room['status'] ?? 'available',
                'max_occupants' => isset($room['max_occupants']) && $room['max_occupants'] !== ''
                    ? (int) $room['max_occupants']
                    : 1,
                'description' => $room['description'] ?? null,
            ])
            ->values()
            ->all();

        unset($data['initial_rooms']);

        $this->adminCredentials = [
            'name' => $data['admin_name'] ?? null,
            'email' => $data['admin_email'] ?? null,
            'password' => $data['admin_password'] ?? null,
        ];

        unset(
            $data['admin_name'],
            $data['admin_email'],
            $data['admin_password'],
            $data['admin_password_confirmation'],
        );

        // Format subdomain based on domain_type
        if ($data['domain_type'] === 'subdomain') {
            $sub = env('APP_DOMAIN');
            // Store only the subdomain part, not the full domain
            $data['domain'] = Str::slug($data['domain']).'.'.$sub;
        }

        // Ensure database name follows the standard format
        $data['database'] = Str::snake($data['database']);

        return $data;
    }

    // after creating a tenant, redirect to the tenant's dashboard
    protected function afterCreate(): void
    {
        try {
            // createTenant:
            // create the database
            DB::statement('CREATE DATABASE IF NOT EXISTS '.$this->record->database);

            // call artisan command to create the database
            // Artisan::call with correct syntax
            Log::info([
                'artisanCommand' => 'migrate:fresh --path=database/migrations/tenant --database=tenant',
                '--tenant' => $this->record->id,
            ]);
            Artisan::call('tenants:artisan', [
                'artisanCommand' => 'migrate:fresh --path=database/migrations/tenant --database=tenant',
                '--tenant' => $this->record->id,
            ]);
            Log::info([
                'artisanCommand' => 'db:seed --class=TenantDatabaseSeeder',
                '--tenant' => $this->record->id,
            ]);
            // php artisan tenants:artisan "migrate --database=tenant --seed"
            Artisan::call('tenants:artisan', [
                'artisanCommand' => 'db:seed --class=TenantDatabaseSeeder',
                '--tenant' => $this->record->id,
            ]);
            Log::info('permission:cache-reset');
            // php artisan permission:cache-reset
            Artisan::call('permission:cache-reset');
            app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            $adminData = $this->adminCredentials;

            if (! blank($adminData['email']) && ! blank($adminData['password'])) {
                $this->record->run(function () use ($adminData) {
                    $user = UserTenant::create([
                        'name' => $adminData['name'] ?? $adminData['email'],
                        'email' => $adminData['email'],
                        'password' => Hash::make($adminData['password']),
                    ]);

                    $user->assignRole('Manager');
                });
            }

            $this->adminCredentials = [];
        } catch (\Exception $e) {
            Log::error('Error creating tenant: '.$e->getMessage());
        }
    }

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
