<?php

namespace App\Services;

use App\Models\Contact;

/**
 * Provides contact-related business logic, keeping controllers lean.
 */
class ContactService
{
    /**
     * Return the next sequential contact number.
     */
    public function nextNumber(): int
    {
        return (int) (Contact::max('number') ?? 0) + 1;
    }
}
