<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Multitenancy\Models\Tenant as BaseTenant;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;

class Tenant extends BaseTenant
{
	use UsesLandlordConnection;
    use HasFactory;

	protected $fillable = [
		'name',
		'domain',
		'database',
		'domain_type',
		'custom_domain',
	];
}
