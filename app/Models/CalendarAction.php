<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CalendarAction extends Model
{
    protected $fillable = ['name'];

    /** Calendar events using this action. */
    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class, 'action_id');
    }
}
