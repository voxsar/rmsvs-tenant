<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;


class RoleHasPermission extends Pivot
{
    //
	use UsesTenantConnection;

	protected $table = 'role_has_permissions';

	//guarded
	protected $guarded = [];
}
