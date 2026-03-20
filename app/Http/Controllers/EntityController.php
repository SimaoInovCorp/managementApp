<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEntityRequest;
use App\Http\Requests\UpdateEntityRequest;
use App\Http\Resources\EntityResource;
use App\Models\Country;
use App\Models\Entity;
use App\Services\EntityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages entities (clients & suppliers).
 *
 * Both client and supplier views share this controller; the active entity
 * type is injected via route defaults as `currentType`.
 */
class EntityController extends Controller
{
    public function __construct(private readonly EntityService $service) {}

    /**
     * List entities, optionally filtered by current type (client|supplier|all).
     */
    public function index(Request $request): Response
    {
        /** @var string $currentType injected by route defaults */
        $currentType = $request->route('currentType', 'all');

        $query = Entity::with('country:id,name,code')
            ->orderBy('name');

        if ($currentType === 'client') {
            $query->whereIn('type', ['client', 'both']);
        } elseif ($currentType === 'supplier') {
            $query->whereIn('type', ['supplier', 'both']);
        }

        return Inertia::render('entities/Index', [
            'entities' => EntityResource::collection($query->paginate(15)),
            'countries' => Country::orderBy('name')->get(['id', 'name', 'code']),
            'currentType' => $currentType,
        ]);
    }

    /**
     * Store a new entity.
     */
    public function store(StoreEntityRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if (! empty($validated['nif']) && $this->service->isNifTaken($validated['nif'])) {
            return back()->withErrors(['nif' => 'An entity with this NIF already exists.'])->withInput();
        }

        Entity::create([
            ...$validated,
            'number' => $this->service->nextNumber(),
        ]);

        return back()->with('success', 'Entity created successfully.');
    }

    /**
     * Update an existing entity.
     */
    public function update(UpdateEntityRequest $request, Entity $entity): RedirectResponse
    {
        $validated = $request->validated();

        if (! empty($validated['nif']) && $this->service->isNifTaken($validated['nif'], $entity->id)) {
            return back()->withErrors(['nif' => 'An entity with this NIF already exists.'])->withInput();
        }

        $entity->update($validated);

        return back()->with('success', 'Entity updated successfully.');
    }

    /**
     * Delete an entity.
     */
    public function destroy(Entity $entity): RedirectResponse
    {
        $entity->delete();

        return back()->with('success', 'Entity deleted.');
    }
}
