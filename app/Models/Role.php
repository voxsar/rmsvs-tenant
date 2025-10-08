<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Role extends SpatieRole
{
    use HasFactory, UsesTenantConnection;

	protected $table = 'roles';
    
    /**
     * Set the default guard name for roles
     */
    protected $guard_name = 'tenant';

	//has many permissions
	public function permissions(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
	{
		return $this->belongsToMany(
			Permission::class, 
			'role_has_permissions', 
			'role_id', 
			'permission_id'
		)->using(RoleHasPermission::class);
	}

	public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
	{
        return $this->morphedByMany(
			UserTenant::class,
			'model',
			'model_has_roles',
			'role_id',
			'model_id'
		)->using(ModelHasRole::class);
	}
	
}
