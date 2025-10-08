<?php

namespace App\Filament\Resources\Landlord\TenantResource\Pages;

use DigitalOceanV2;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use App\Filament\Resources\Landlord\TenantResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Format subdomain based on domain_type
        if ($data['domain_type'] === 'subdomain') {
            // Store only the subdomain part, not the full domain
            $data['domain'] = Str::snake($data['domain']);
        }
        
        // Ensure database name follows the standard format
        $data['database'] = Str::snake($data['database']);
        
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
			->before(function () {
				// ...
				$client = new DigitalOceanV2\Client();
				$client->authenticate(config('services.digitalocean.token'));

				$domainRecord = $client->domainRecord();
				$records = $domainRecord->getAll(config('services.digitalocean.domain'));
				
				// Get the subdomain
				$subdomain = $this->record->domain;
				
				Log::info('Delete subdomain Name: ' . $subdomain);
				foreach ($records as $record) {
					Log::info('Delete Record Name: ' . $record->name);
					if ($record->name == $subdomain) {
						$domainRecord->remove(config('services.digitalocean.domain'), $record->id);
					}
				}
				
				// If this tenant has a custom domain, try to remove the CNAME record
				if ($this->record->domain_type === 'domain' && !empty($this->record->custom_domain)) {
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
								// Find and remove CNAME record in the custom domain
								$customRecords = $domainRecord->getAll($customDomain);
								foreach ($customRecords as $record) {
									if ($record->name == $customSubdomain && $record->type == 'CNAME') {
										$domainRecord->remove($customDomain, $record->id);
										Log::info("Removed CNAME record for {$this->record->custom_domain}");
									}
								}
							}
						} catch (\Exception $e) {
							Log::error("Error removing CNAME record: " . $e->getMessage());
						}
					} catch (\Exception $e) {
						Log::error("Error processing custom domain during deletion: " . $e->getMessage());
					}
				}

				DB::statement('DROP DATABASE IF EXISTS ' . $this->record->database);
			}),
        ];
    }

	//afterSave
	protected function afterSave(): void
	{
		try {
			//client
			$client = new DigitalOceanV2\Client();
			$client->authenticate(config('services.digitalocean.token'));

			$domainRecord = $client->domainRecord();
			$records = $domainRecord->getAll(config('services.digitalocean.domain'));
			$recordExists = false;
			$subdomain = $this->record->domain;
			
			foreach ($records as $record) {
				Log::info('Record Name: ' . $record->name);
				if ($record->name == $subdomain) {
					$domainRecord->remove(config('services.digitalocean.domain'), $record->id);
					$recordExists = true;
				}
			}

			// Create or recreate the subdomain record
			$domainRecord->create(config('services.digitalocean.domain'), 'A', $subdomain, config('services.digitalocean.ip'));
			
			// If this tenant has a custom domain, create or update the CNAME record
			if ($this->record->domain_type === 'domain' && !empty($this->record->custom_domain)) {
				// Get the full subdomain
				$fullSubdomain = $subdomain . '.' . config('services.digitalocean.domain');
				
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
							// Check if CNAME record exists
							$customRecordExists = false;
							$customRecords = $domainRecord->getAll($customDomain);
							
							foreach ($customRecords as $record) {
								if ($record->name == $customSubdomain && $record->type == 'CNAME') {
									// Update record by removing and recreating
									$domainRecord->remove($customDomain, $record->id);
									$customRecordExists = true;
								}
							}
							
							// Create CNAME record in the custom domain pointing to our subdomain
							$domainRecord->create($customDomain, 'CNAME', $customSubdomain, $fullSubdomain);
							Log::info("Created/Updated CNAME record for {$this->record->custom_domain} pointing to {$fullSubdomain}");
						} else {
							Log::warning("Could not create CNAME record - domain {$customDomain} is not managed in DigitalOcean");
						}
					} catch (\Exception $e) {
						Log::error("Error managing CNAME record: " . $e->getMessage());
					}
				} catch (\Exception $e) {
					Log::error("Error processing custom domain: " . $e->getMessage());
				}
			}
			
			DB::statement('CREATE DATABASE IF NOT EXISTS ' . $this->record->database);
			
			Log::info('Database created successfully');
			
			// Artisan::call with correct syntax
			Artisan::call('tenants:artisan', [
				'--tenant' => $this->record->id,
				'artisanCommand' => '"migrate:fresh --path=database/migrations/tenant --database=tenant"'
			]);
			
			// Redirect to the tenant's dashboard
			$this->redirect($this->getResource()::getUrl('index'));
		} catch (\Exception $e) {
			Log::error('Error updating tenant: ' . $e->getMessage());
		}
	}
}
