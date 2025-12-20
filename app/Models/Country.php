<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $fillable = [
        'name',
        'code',
        'flag',
    ];

    public function leagues(): HasMany
    {
        return $this->hasMany(League::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }
}
