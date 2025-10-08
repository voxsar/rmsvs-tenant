<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Scanner extends Model
{
	use UsesTenantConnection;
    use HasFactory;

	protected $fillable = [
		'name',
		'location',
		'status',
		'type',
	];
}
