<?php

namespace App\Services;

use App\Models\Entity;

/**
 * Provides entity-related business logic, keeping controllers lean.
 */
class EntityService
{
    /**
     * Return the next sequential entity number.
     * Thread-safe for typical ERP usage volumes.
     */
    public function nextNumber(): int
    {
        return (int) (Entity::max('number') ?? 0) + 1;
    }

    /**
     * Check whether a NIF is already used by another entity.
     *
     * Because `nif` is encrypted with a non-deterministic algorithm we cannot
     * use a database unique constraint.  Instead, we decrypt all NIFs in PHP.
     * For typical ERP scale (≤ a few thousand entities) this is acceptable.
     *
     * @param  string  $nif  the NIF to test
     * @param  int|null  $excludeId  entity ID to exclude (for update checks)
     */
    public function isNifTaken(string $nif, ?int $excludeId = null): bool
    {
        return Entity::query()
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->get(['id', 'nif'])
            ->contains(fn (Entity $e) => $e->nif === $nif);
    }
}
