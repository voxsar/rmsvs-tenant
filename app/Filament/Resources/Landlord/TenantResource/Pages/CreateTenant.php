<?php

namespace App\Filament\Resources\Landlord\TenantResource\Pages;

use DigitalOceanV2;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use App\Filament\Resources\Landlord\TenantResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Format subdomain based on domain_type
        if ($data['domain_type'] === 'subdomain') {
            $sub = env('APP_DOMAIN');
            // Store only the subdomain part, not the full domain
            $data['domain'] = Str::slug($data['domain']) . '.' . $sub;
        }
        
        // Ensure database name follows the standard format
        $data['database'] = Str::snake($data['database']);
		
        return $data;
    }

	//after creating a tenant, redirect to the tenant's dashboard
	protected function afterCreate(): void
	{
		try{
			//goto createTenant;
			//client
			/*$client = new DigitalOceanV2\Client();
			$client->authenticate(config('services.digitalocean.token'));

			$domainRecord = $client->domainRecord();
			$records = $domainRecord->getAll(config('services.digitalocean.domain'));
			
			// Get the subdomain from the domain field
			$subdomain = $this->record->domain;
			$sub = env('APP_DOMAIN');
			$subdomain = str_replace('.' . $sub, '', $subdomain);
			$recordExists = false;
			
			// Check if the subdomain record exists
			foreach ($records as $record) {
				Log::info('Record Name: ' . $record->name);
				if ($record->name == $subdomain) {
					$recordExists = true;
					break;
				}
			}

			// Create or update the subdomain record
			if (!$recordExists) {
				$domainRecord->create(config('services.digitalocean.domain'), 'A', $subdomain, config('services.digitalocean.ip'));
			}
			
			// If this tenant has a custom domain, create a CNAME record that points to the subdomain
			if ($this->record->domain_type === 'domain' && !empty($this->record->custom_domain)) {
				// Create a CNAME record for the custom domain pointing to the subdomain
				// First we need to determine the full subdomain
				$fullSubdomain = $subdomain . '.' . config('services.digitalocean.domain');
				
				// For simplicity, we'll assume the custom domain doesn't exist in DO
				// In a real application, you might want to check and update existing records
				try {
					// Get the domain and subdomain parts from the custom domain
					$customDomainParts = explode('.', $this->record->custom_domain);
					$customSubdomain = $customDomainParts[0];
					$customDomain = implode('.', array_slice($customDomainParts, 1));
					
					// Check if we can manage this domain in DO
					$domainExists = false;
					try {
						$domains = $client->domain()->getAll();
						foreach ($domains as $domain) {
							if ($domain->name === $customDomain) {
								$domainExists = true;
								break;
							}
						}
						
						if ($domainExists) {
							// Create CNAME record in the custom domain pointing to our subdomain
							$domainRecord->create($customDomain, 'CNAME', $customSubdomain, $fullSubdomain);
							Log::info("Created CNAME record for {$this->record->custom_domain} pointing to {$fullSubdomain}");
						} else {
							Log::warning("Could not create CNAME record - domain {$customDomain} is not managed in DigitalOcean");
						}
					} catch (\Exception $e) {
						Log::error("Error creating CNAME record: " . $e->getMessage());
					}
				} catch (\Exception $e) {
					Log::error("Error processing custom domain: " . $e->getMessage());
				}
			}*/
			//createTenant:
			//create the database
			DB::statement('CREATE DATABASE IF NOT EXISTS ' . $this->record->database);
			
			//call artisan command to create the database
			// Artisan::call with correct syntax
			Log::info([
				'artisanCommand' => 'migrate:fresh --path=database/migrations/tenant --database=tenant',
				'--tenant' => $this->record->id
			]);
			Artisan::call('tenants:artisan', [
				'artisanCommand' => 'migrate:fresh --path=database/migrations/tenant --database=tenant',
				'--tenant' => $this->record->id
			]);
			Log::info([
				'artisanCommand' => 'db:seed --class=TenantDatabaseSeeder',
				'--tenant' => $this->record->id
			]);
			//php artisan tenants:artisan "migrate --database=tenant --seed"
			Artisan::call('tenants:artisan', [
				'artisanCommand' => 'db:seed --class=TenantDatabaseSeeder',
				'--tenant' => $this->record->id
			]);
			Log::info("permission:cache-reset");
			//php artisan permission:cache-reset
			Artisan::call('permission:cache-reset');
			app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
			//$this->redirect($this->getResource()::getUrl('index'));
		}catch (\Exception $e) {
			Log::error('Error creating tenant: ' . $e->getMessage());
		}
	}

	protected function getActions(): array
	{
		return [
			Actions\CreateAction::make(),
		];
	}
}
