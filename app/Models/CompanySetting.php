<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Single-row settings table.
 * Always retrieved via CompanySetting::firstOrNew([]) to ensure only one record exists.
 */
class CompanySetting extends Model
{
    protected $fillable = [
        'name',
        'address',
        'postal_code',
        'locality',
        'tax_number',
        'logo_path',
    ];
}
