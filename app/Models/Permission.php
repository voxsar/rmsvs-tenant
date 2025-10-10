<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use HasFactory, UsesTenantConnection;

	protected $table = 'permissions';

    /**
     * A permission can be applied to roles.
     */
    public function roles(): BelongsToMany
    {
		return $this->belongsToMany(
			Role::class, 
			'role_has_permissions', 
			'permission_id', 
			'role_id'
		)->using(RoleHasPermission::class);
    }

    /**
     * A permission belongs to some users of the model associated with its guard.
     */
    public function users(): BelongsToMany
    {
        return $this->morphedByMany(
			User::class,
			'model',
			'model_has_permissions',
			'permission_id',
			'model_id'
		)->using(ModelHasPermission::class);
    }
}
