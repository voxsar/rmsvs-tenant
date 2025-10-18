<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;
use Spatie\Multitenancy\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant
{
    use HasFactory;
    use UsesLandlordConnection;

    protected $fillable = [
        'name',
        'domain',
        'database',
        'domain_type',
        'custom_domain',
    ];
}
