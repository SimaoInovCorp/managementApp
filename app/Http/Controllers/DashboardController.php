<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\CalendarEvent;
use App\Models\Contact;
use App\Models\CustomerOrder;
use App\Models\DigitalArchive;
use App\Models\Entity;
use App\Models\Proposal;
use App\Models\SupplierInvoice;
use App\Models\SupplierOrder;
use App\Models\WorkOrder;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Render the application dashboard with aggregated statistics.
     */
    public function __invoke(): Response
    {
        $stats = [
            'entities' => [
                'total'    => Entity::count(),
                'clients'  => Entity::whereIn('type', ['client', 'both'])->count(),
                'suppliers' => Entity::whereIn('type', ['supplier', 'both'])->count(),
                'active'   => Entity::where('status', 'active')->count(),
            ],
            'contacts'       => Contact::count(),
            'articles'       => Article::where('status', 'active')->count(),
            'proposals'      => [
                'total'  => Proposal::count(),
                'draft'  => Proposal::where('status', 'draft')->count(),
                'closed' => Proposal::where('status', 'closed')->count(),
            ],
            'customerOrders' => [
                'total'  => CustomerOrder::count(),
                'draft'  => CustomerOrder::where('status', 'draft')->count(),
                'closed' => CustomerOrder::where('status', 'closed')->count(),
            ],
            'supplierOrders' => [
                'total'  => SupplierOrder::count(),
                'draft'  => SupplierOrder::where('status', 'draft')->count(),
                'closed' => SupplierOrder::where('status', 'closed')->count(),
            ],
            'workOrders'     => WorkOrder::count(),
            'invoices'       => [
                'total'   => SupplierInvoice::count(),
                'pending' => SupplierInvoice::where('status', 'pending')->count(),
                'paid'    => SupplierInvoice::where('status', 'paid')->count(),
            ],
            'calendarEvents' => CalendarEvent::count(),
            'archive'        => DigitalArchive::count(),
        ];

        return Inertia::render('Dashboard', ['stats' => $stats]);
    }
}
