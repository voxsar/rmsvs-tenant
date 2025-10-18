<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Consumable extends Model
{
    use HasFactory;
    use UsesTenantConnection;

    protected $fillable = [
        'name',
        'description',
        'price',
        'is_visible', // Add visibility control
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_visible' => 'boolean',
    ];

    public function customrequests(): HasMany
    {
        return $this->hasMany(CustomRequest::class, 'consumable_id');
    }
}
