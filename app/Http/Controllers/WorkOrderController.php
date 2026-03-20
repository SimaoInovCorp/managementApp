<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkOrderRequest;
use App\Http\Requests\UpdateWorkOrderRequest;
use App\Http\Resources\WorkOrderResource;
use App\Models\Entity;
use App\Models\WorkOrder;
use App\Services\WorkOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages the work orders lifecycle: listing, create/edit/delete.
 */
class WorkOrderController extends Controller
{
    public function __construct(private readonly WorkOrderService $service) {}

    /**
     * List all work orders with their client.
     */
    public function index(Request $request): Response
    {
        $orders = WorkOrder::with('client:id,name,type')
            ->orderByDesc('date')
            ->orderByDesc('number')
            ->paginate(15);

        $clients = Entity::whereIn('type', ['client', 'both'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        return Inertia::render('work_orders/Index', [
            'orders' => WorkOrderResource::collection($orders),
            'clients' => $clients,
        ]);
    }

    /**
     * Create a new work order.
     */
    public function store(StoreWorkOrderRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['number'] = $this->service->nextNumber();

        WorkOrder::create($data);

        return back()->with('success', 'Work order created successfully.');
    }

    /**
     * Update an existing work order.
     */
    public function update(UpdateWorkOrderRequest $request, WorkOrder $order): RedirectResponse
    {
        $order->update($request->validated());

        return back()->with('success', 'Work order updated successfully.');
    }

    /**
     * Delete a work order.
     */
    public function destroy(WorkOrder $order): RedirectResponse
    {
        $order->delete();

        return back()->with('success', 'Work order deleted.');
    }
}
