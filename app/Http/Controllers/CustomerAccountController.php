<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerAccountRequest;
use App\Http\Resources\CustomerAccountResource;
use App\Models\CustomerAccount;
use App\Models\Entity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages the append-only customer current account ledger.
 * No update or delete — entries are immutable once created.
 */
class CustomerAccountController extends Controller
{
    /**
     * List all ledger entries with optional entity filter.
     * Computes a running balance per entry.
     */
    public function index(Request $request): Response
    {
        $query = CustomerAccount::with('entity:id,name')
            ->orderBy('date')
            ->orderBy('id');

        if ($request->filled('entity_id')) {
            $query->where('entity_id', $request->integer('entity_id'));
        }

        $entries = $query->get();

        // Compute running balance per entry (for entire set, regardless of filter)
        $running = 0;
        $entries = $entries->map(function (CustomerAccount $entry) use (&$running): CustomerAccount {
            $running += (float) $entry->credit - (float) $entry->debit;
            $entry->running_balance = round($running, 2);

            return $entry;
        });

        $clients = Entity::whereIn('type', ['client', 'both'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('financial/CustomerAccounts', [
            'entries' => CustomerAccountResource::collection($entries),
            'clients' => $clients,
            'selectedEntityId' => $request->integer('entity_id') ?: null,
        ]);
    }

    /**
     * Append a new ledger entry (no update/delete allowed).
     */
    public function store(StoreCustomerAccountRequest $request): RedirectResponse
    {
        CustomerAccount::create($request->validated());

        return back()->with('success', 'Ledger entry added successfully.');
    }
}
