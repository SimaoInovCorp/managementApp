<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $fillable = ['name', 'code'];

    /** Entities located in this country. */
    public function entities(): HasMany
    {
        return $this->hasMany(Entity::class);
    }
}
