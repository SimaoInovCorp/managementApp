<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Represents a product or service in the catalogue.
 *
 * @property int $id
 * @property string $reference unique product code
 * @property string $name
 * @property string|null $description
 * @property float $price net price (before VAT)
 * @property int $vat_id
 * @property string|null $photo_path relative path in the private disk
 * @property string|null $notes
 * @property string $status active | inactive
 */
class Article extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'reference',
        'name',
        'description',
        'price',
        'vat_id',
        'photo_path',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    /** The VAT rate applied to this article. */
    public function vatRate(): BelongsTo
    {
        return $this->belongsTo(VatRate::class, 'vat_id');
    }

    // ─── Activity Log ─────────────────────────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['reference', 'name', 'price', 'status'])
            ->logOnlyDirty()
            ->useLogName('articles')
            ->dontSubmitEmptyLogs();
    }
}
