<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class ModelHasPermission extends Pivot
{
    //
    use UsesTenantConnection;

	protected $table = 'model_has_permissions';
	//guarded
	protected $guarded = [];
}
