<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use App\Models\ContactRole;
use App\Models\Entity;
use App\Services\ContactService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages contacts linked to entities (clients & suppliers).
 */
class ContactController extends Controller
{
    public function __construct(private readonly ContactService $service) {}

    /**
     * List all contacts, optionally filtered by entity.
     *
     * Query params:
     *   - entity_id (int) — restrict to a single entity
     */
    public function index(Request $request): Response
    {
        $query = Contact::with(['entity:id,name,type', 'role:id,name'])
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($entityId = $request->integer('entity_id', 0)) {
            $query->where('entity_id', $entityId);
        }

        return Inertia::render('contacts/Index', [
            'contacts' => ContactResource::collection($query->paginate(15)),
            'entities' => Entity::orderBy('name')->get(['id', 'name', 'type']),
            'roles' => ContactRole::orderBy('name')->get(['id', 'name']),
            'filters' => ['entity_id' => $request->integer('entity_id', 0) ?: null],
        ]);
    }

    /**
     * Store a new contact.
     */
    public function store(StoreContactRequest $request): RedirectResponse
    {
        Contact::create([
            ...$request->validated(),
            'number' => $this->service->nextNumber(),
        ]);

        return back()->with('success', 'Contact created successfully.');
    }

    /**
     * Update an existing contact.
     */
    public function update(UpdateContactRequest $request, Contact $contact): RedirectResponse
    {
        $contact->update($request->validated());

        return back()->with('success', 'Contact updated successfully.');
    }

    /**
     * Delete a contact.
     */
    public function destroy(Contact $contact): RedirectResponse
    {
        $contact->delete();

        return back()->with('success', 'Contact deleted.');
    }
}
