<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBankAccountRequest;
use App\Http\Requests\UpdateBankAccountRequest;
use App\Http\Resources\BankAccountResource;
use App\Models\BankAccount;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages bank accounts (encrypted IBAN, CRUD).
 */
class BankAccountController extends Controller
{
    /**
     * List all bank accounts.
     */
    public function index(): Response
    {
        $accounts = BankAccount::orderBy('name')->paginate(15);

        return Inertia::render('financial/BankAccounts', [
            'accounts' => BankAccountResource::collection($accounts),
        ]);
    }

    /**
     * Store a new bank account.
     */
    public function store(StoreBankAccountRequest $request): RedirectResponse
    {
        BankAccount::create($request->validated());

        return back()->with('success', 'Bank account created successfully.');
    }

    /**
     * Update an existing bank account.
     */
    public function update(UpdateBankAccountRequest $request, BankAccount $account): RedirectResponse
    {
        $account->update($request->validated());

        return back()->with('success', 'Bank account updated successfully.');
    }

    /**
     * Delete a bank account.
     */
    public function destroy(BankAccount $account): RedirectResponse
    {
        $account->delete();

        return back()->with('success', 'Bank account deleted.');
    }
}
