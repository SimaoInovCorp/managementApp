<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VatRate extends Model
{
    protected $fillable = ['name', 'rate'];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
        ];
    }

    /** Articles using this VAT rate. */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'vat_id');
    }
}
