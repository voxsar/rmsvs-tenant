<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class DailyReport extends Model
{
    use HasFactory;
    use UsesTenantConnection;

    protected $fillable = [
        'date',
        'content',
    ];

    protected $casts = [
        'date' => 'date',
    ];
}
