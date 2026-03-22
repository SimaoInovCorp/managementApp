<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ArcElement, Chart as ChartJS, Legend, Tooltip } from 'chart.js';
import {
    Archive,
    BookOpen,
    Building2,
    CalendarDays,
    FileText,
    Package,
    ReceiptText,
    ShoppingCart,
    Truck,
    UserRound,
    Wrench,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { Doughnut } from 'vue-chartjs';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

ChartJS.register(ArcElement, Tooltip, Legend);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
];

interface DashboardStats {
    entities: {
        total: number;
        clients: number;
        suppliers: number;
        active: number;
    };
    contacts: number;
    articles: number;
    proposals: { total: number; draft: number; closed: number };
    customerOrders: { total: number; draft: number; closed: number };
    supplierOrders: { total: number; draft: number; closed: number };
    workOrders: number;
    invoices: { total: number; pending: number; paid: number };
    calendarEvents: number;
    archive: number;
}

const props = defineProps<{ stats: DashboardStats }>();

// ─── Chart colours ──────────────────────────────────────────────────────────
const CHART_COLORS = {
    red: '#e53e3e',
    dark: '#1a1a1a',
    gray: '#6b7280',
    slate: '#475569',
    rose: '#f43f5e',
};

const entitiesChartData = computed(() => ({
    labels: ['Clients', 'Suppliers'],
    datasets: [
        {
            data: [
                props.stats.entities.clients,
                props.stats.entities.suppliers,
            ],
            backgroundColor: [CHART_COLORS.red, CHART_COLORS.dark],
            borderColor: ['#fff', '#fff'],
            borderWidth: 2,
            hoverOffset: 6,
        },
    ],
}));

const proposalsChartData = computed(() => ({
    labels: ['Draft', 'Closed'],
    datasets: [
        {
            data: [props.stats.proposals.draft, props.stats.proposals.closed],
            backgroundColor: [CHART_COLORS.gray, CHART_COLORS.red],
            borderColor: ['#fff', '#fff'],
            borderWidth: 2,
            hoverOffset: 6,
        },
    ],
}));

const ordersChartData = computed(() => ({
    labels: ['Customer Orders', 'Supplier Orders', 'Work Orders'],
    datasets: [
        {
            data: [
                props.stats.customerOrders.total,
                props.stats.supplierOrders.total,
                props.stats.workOrders,
            ],
            backgroundColor: [
                CHART_COLORS.red,
                CHART_COLORS.dark,
                CHART_COLORS.slate,
            ],
            borderColor: ['#fff', '#fff', '#fff'],
            borderWidth: 2,
            hoverOffset: 6,
        },
    ],
}));

const invoicesChartData = computed(() => ({
    labels: ['Pending', 'Paid'],
    datasets: [
        {
            data: [props.stats.invoices.pending, props.stats.invoices.paid],
            backgroundColor: [CHART_COLORS.rose, CHART_COLORS.dark],
            borderColor: ['#fff', '#fff'],
            borderWidth: 2,
            hoverOffset: 6,
        },
    ],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom' as const,
            labels: { padding: 16, font: { size: 12 } },
        },
    },
    cutout: '60%',
};

const features = [
    {
        icon: Building2,
        title: 'Entities',
        desc: 'Manage clients and suppliers with VIES VAT lookup and full contact details.',
    },
    {
        icon: UserRound,
        title: 'Contacts',
        desc: 'Link contacts to entities with configurable roles and GDPR consent tracking.',
    },
    {
        icon: Package,
        title: 'Articles',
        desc: 'Product catalogue with photos, pricing, and VAT rate assignments.',
    },
    {
        icon: FileText,
        title: 'Proposals',
        desc: 'Create proposals with line items, generate PDF exports, and convert to orders in one click.',
    },
    {
        icon: ShoppingCart,
        title: 'Customer Orders',
        desc: 'Full order management with PDF generation and automatic supplier order creation.',
    },
    {
        icon: Truck,
        title: 'Supplier Orders',
        desc: 'Auto-generated or manual supplier orders grouped by supplier.',
    },
    {
        icon: Wrench,
        title: 'Work Orders',
        desc: 'Track work orders linked to clients with status management.',
    },
    {
        icon: ReceiptText,
        title: 'Financial',
        desc: 'Bank accounts, customer current accounts, and supplier invoices with payment confirmation emails.',
    },
    {
        icon: CalendarDays,
        title: 'Calendar',
        desc: 'FullCalendar integration with per-user and per-entity event filtering and knowledge notes.',
    },
    {
        icon: Archive,
        title: 'Digital Archive',
        desc: 'Secure document storage outside public_html with authenticated downloads.',
    },
    {
        icon: BookOpen,
        title: 'Activity Logs',
        desc: 'Complete audit trail of all actions with user, IP, and device tracking.',
    },
    {
        icon: Building2,
        title: 'Access Management',
        desc: 'Role-based permission groups with a full CRUD matrix per menu section.',
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-8 p-6">
            <!-- ── Welcome Banner ─────────────────────────────────────── -->
            <div
                class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-black via-neutral-900 to-red-950 p-8 text-white shadow-xl"
            >
                <!-- decorative glow -->
                <div
                    class="pointer-events-none absolute -top-16 -right-16 size-72 rounded-full bg-red-700/30 blur-3xl"
                />
                <div
                    class="pointer-events-none absolute -bottom-12 -left-12 size-56 rounded-full bg-red-900/20 blur-2xl"
                />

                <div class="relative">
                    <div class="mb-2 flex items-center gap-2">
                        <span class="text-4xl font-black tracking-tight">
                            ino<span class="text-red-500">V</span>corp
                        </span>
                        <Badge class="bg-red-600 text-white hover:bg-red-700"
                            >Management</Badge
                        >
                    </div>
                    <h1 class="text-2xl font-semibold text-white/90">
                        Welcome back to your business hub
                    </h1>
                    <p class="mt-1 max-w-2xl text-sm text-white/60">
                        Everything you need to manage clients, orders, finances,
                        and your team — in one place. Use the sidebar to
                        navigate between modules.
                    </p>
                </div>
            </div>

            <!-- ── Stat Cards ─────────────────────────────────────────── -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card class="border-l-4 border-l-red-600">
                    <CardHeader class="pb-2">
                        <CardDescription>Entities</CardDescription>
                        <CardTitle class="text-3xl font-bold">{{
                            stats.entities.total
                        }}</CardTitle>
                    </CardHeader>
                    <CardContent class="text-xs text-muted-foreground">
                        {{ stats.entities.clients }} clients ·
                        {{ stats.entities.suppliers }} suppliers
                    </CardContent>
                </Card>

                <Card class="border-l-4 border-l-red-600">
                    <CardHeader class="pb-2">
                        <CardDescription>Proposals</CardDescription>
                        <CardTitle class="text-3xl font-bold">{{
                            stats.proposals.total
                        }}</CardTitle>
                    </CardHeader>
                    <CardContent class="text-xs text-muted-foreground">
                        {{ stats.proposals.draft }} draft ·
                        {{ stats.proposals.closed }} closed
                    </CardContent>
                </Card>

                <Card class="border-l-4 border-l-red-600">
                    <CardHeader class="pb-2">
                        <CardDescription>Orders</CardDescription>
                        <CardTitle class="text-3xl font-bold">
                            {{
                                stats.customerOrders.total +
                                stats.supplierOrders.total
                            }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="text-xs text-muted-foreground">
                        {{ stats.customerOrders.total }} customer ·
                        {{ stats.supplierOrders.total }} supplier
                    </CardContent>
                </Card>

                <Card class="border-l-4 border-l-red-600">
                    <CardHeader class="pb-2">
                        <CardDescription>Invoices</CardDescription>
                        <CardTitle class="text-3xl font-bold">{{
                            stats.invoices.total
                        }}</CardTitle>
                    </CardHeader>
                    <CardContent class="text-xs text-muted-foreground">
                        {{ stats.invoices.pending }} pending ·
                        {{ stats.invoices.paid }} paid
                    </CardContent>
                </Card>
            </div>

            <!-- ── Charts ─────────────────────────────────────────────── -->
            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium"
                            >Entities by Type</CardTitle
                        >
                    </CardHeader>
                    <CardContent class="flex h-52 items-center justify-center">
                        <Doughnut
                            v-if="stats.entities.total > 0"
                            :data="entitiesChartData"
                            :options="chartOptions"
                        />
                        <span v-else class="text-sm text-muted-foreground"
                            >No data yet</span
                        >
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium"
                            >Proposal Status</CardTitle
                        >
                    </CardHeader>
                    <CardContent class="flex h-52 items-center justify-center">
                        <Doughnut
                            v-if="stats.proposals.total > 0"
                            :data="proposalsChartData"
                            :options="chartOptions"
                        />
                        <span v-else class="text-sm text-muted-foreground"
                            >No data yet</span
                        >
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium"
                            >Orders Distribution</CardTitle
                        >
                    </CardHeader>
                    <CardContent class="flex h-52 items-center justify-center">
                        <Doughnut
                            v-if="
                                stats.customerOrders.total +
                                    stats.supplierOrders.total +
                                    stats.workOrders >
                                0
                            "
                            :data="ordersChartData"
                            :options="chartOptions"
                        />
                        <span v-else class="text-sm text-muted-foreground"
                            >No data yet</span
                        >
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium"
                            >Invoice Status</CardTitle
                        >
                    </CardHeader>
                    <CardContent class="flex h-52 items-center justify-center">
                        <Doughnut
                            v-if="stats.invoices.total > 0"
                            :data="invoicesChartData"
                            :options="chartOptions"
                        />
                        <span v-else class="text-sm text-muted-foreground"
                            >No data yet</span
                        >
                    </CardContent>
                </Card>
            </div>

            <!-- ── Quick Stats Row ─────────────────────────────────────── -->
            <div class="grid gap-4 sm:grid-cols-3">
                <Card class="bg-muted/40">
                    <CardContent class="flex items-center gap-4 pt-5">
                        <UserRound class="size-8 shrink-0 text-red-600" />
                        <div>
                            <div class="text-2xl font-bold">
                                {{ stats.contacts }}
                            </div>
                            <div class="text-xs text-muted-foreground">
                                Contacts registered
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card class="bg-muted/40">
                    <CardContent class="flex items-center gap-4 pt-5">
                        <Package class="size-8 shrink-0 text-red-600" />
                        <div>
                            <div class="text-2xl font-bold">
                                {{ stats.articles }}
                            </div>
                            <div class="text-xs text-muted-foreground">
                                Active articles
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card class="bg-muted/40">
                    <CardContent class="flex items-center gap-4 pt-5">
                        <Archive class="size-8 shrink-0 text-red-600" />
                        <div>
                            <div class="text-2xl font-bold">
                                {{ stats.archive }}
                            </div>
                            <div class="text-xs text-muted-foreground">
                                Archived documents
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- ── Feature Overview ───────────────────────────────────── -->
            <div>
                <h2 class="mb-4 text-lg font-semibold">What you can do</h2>
                <div
                    class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                >
                    <Card
                        v-for="f in features"
                        :key="f.title"
                        class="transition-shadow hover:shadow-md"
                    >
                        <CardContent class="flex items-start gap-3 pt-5">
                            <component
                                :is="f.icon"
                                class="mt-0.5 size-5 shrink-0 text-red-600"
                            />
                            <div>
                                <div class="text-sm font-semibold">
                                    {{ f.title }}
                                </div>
                                <div
                                    class="mt-0.5 text-xs leading-relaxed text-muted-foreground"
                                >
                                    {{ f.desc }}
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
