<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class ModelHasRole extends Pivot
{
    //
    use UsesTenantConnection;

    protected $table = 'model_has_roles';

    // guarded
    protected $guarded = [];

    // no timestamps
    public $timestamps = false;
}
