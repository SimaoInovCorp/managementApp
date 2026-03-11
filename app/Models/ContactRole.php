<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContactRole extends Model
{
    protected $fillable = ['name'];

    /** Contacts assigned this role. */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class, 'role_id');
    }
}
