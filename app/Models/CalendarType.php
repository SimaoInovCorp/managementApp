<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CalendarType extends Model
{
    protected $fillable = ['name'];

    /** Calendar events of this type. */
    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class, 'type_id');
    }
}
