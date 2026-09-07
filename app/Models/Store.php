<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'business_entity',
        'type',
        'area',
        'regional',
        'address',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
        'type'   => 'string',
    ];

    // Relationships
    public function audits(): HasMany
    {
        return $this->hasMany(Audit::class);
    }

    public function auditees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'store_user');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
