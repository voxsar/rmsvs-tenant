<?php

namespace App\Services;

use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;

class ProcessTask implements SwitchTenantTask
{
    public function __construct(protected ?string $originalPrefix = null) {}

    public function makeCurrent(Tenant $tenant): void {}

    public function forgetCurrent(): void {}

    protected function setCachePrefix(string $prefix): void {}
}
