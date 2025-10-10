<?php

namespace App\Filament\Table\Actions;

use DigitalOceanV2;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Filament\Tables\Actions\DeleteBulkAction as DeleteBulkActionBase;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
class DeleteBulkAction extends DeleteBulkActionBase{
	protected function setUp(): void
	{
		parent::setUp();

		$this->modalHeading(fn (): string => __('filament-actions::delete.multiple.modal.heading', ['label' => $this->getPluralModelLabel()]));

		$this->modalSubmitActionLabel(__('filament-actions::delete.multiple.modal.actions.delete.label'));

		$this->successNotificationTitle(__('filament-actions::delete.multiple.notifications.deleted.title'));

		$this->color('danger');

		$this->icon('heroicon-o-trash');

		$this->requiresConfirmation();

		$this->modalIcon('heroicon-o-trash');

		
        $this->action(function (): void {
            $this->process(function (Collection $records) {
				$records->each(function (Model $record) {

					$client = new DigitalOceanV2\Client();
					$client->authenticate(config('services.digitalocean.token'));

					$domainRecord = $client->domainRecord();
					$records = $domainRecord->getAll(config('services.digitalocean.domain'));
					
					$subdomain = str_replace(".".config('services.digitalocean.domain'), '', $record->domain);
					
					Log::info('Delete subdomain Name: ' . $subdomain);
					foreach ($records as $recordlist) {
						Log::info('Delete Record Name: ' . $recordlist->name);
						if ($recordlist->name == $subdomain) {
							$domainRecord->remove(config('services.digitalocean.domain'), $recordlist->id);
						}
					}

					DB::statement('DROP DATABASE IF EXISTS ' . $record->database);
					// Perform the delete action
					$record->delete();
				});
			});

            $this->success();
        });
		
	}
}