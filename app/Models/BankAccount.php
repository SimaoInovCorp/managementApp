<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Bank account with encrypted IBAN storage.
 */
class BankAccount extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'iban',
        'bic',
        'balance',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'iban' => 'encrypted',
            'balance' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logExcept(['iban'])
            ->useLogName('bank_accounts');
    }
}
