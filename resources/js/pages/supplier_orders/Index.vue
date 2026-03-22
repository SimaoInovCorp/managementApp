<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import SupplierOrderController from '@/actions/App/Http/Controllers/SupplierOrderController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetContent,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as supplierOrdersIndex } from '@/routes/supplier_orders';
import type { BreadcrumbItem } from '@/types';

// ─── Types ────────────────────────────────────────────────────────────────────

type ArticleOption = {
    id: number;
    reference: string;
    name: string;
    price: number;
    vat_id: number;
    vat_rate: number;
};

type EntityOption = { id: number; name: string };
type CustomerOrderOption = { id: number; number: number; status: string };

type OrderLineForm = {
    article_id: number | null;
    quantity: string;
    unit_price: string;
    vat_rate: number;
    article_name: string;
};

type OrderLineData = {
    id: number;
    article_id: number;
    article: {
        id: number;
        reference: string;
        name: string;
        price: number;
        vat_id: number;
        vat_rate: number;
    } | null;
    quantity: number;
    unit_price: number;
    sort_order: number;
};

type OrderRow = {
    id: number;
    number: number;
    order_date: string;
    supplier_id: number;
    supplier: { id: number; name: string } | null;
    customer_order_id: number | null;
    customer_order: { id: number; number: number } | null;
    total_amount: number;
    status: 'draft' | 'closed';
    notes: string | null;
    lines: OrderLineData[];
    created_at: string;
};

// ─── Props ────────────────────────────────────────────────────────────────────

const props = defineProps<{
    orders: { data: OrderRow[] };
    suppliers: EntityOption[];
    customerOrders: CustomerOrderOption[];
    articles: ArticleOption[];
}>();

// ─── Breadcrumbs ──────────────────────────────────────────────────────────────

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Supplier Orders', href: supplierOrdersIndex() },
];

// ─── Flash ────────────────────────────────────────────────────────────────────

const page = usePage();
const flash = computed(
    () =>
        page.props.flash as { success?: string | null; error?: string | null },
);

// ─── Sheet state ──────────────────────────────────────────────────────────────

const sheetOpen = ref(false);
const editingId = ref<number | null>(null);

const form = useForm({
    order_date: '',
    supplier_id: null as number | null,
    customer_order_id: null as number | null,
    status: 'draft' as 'draft' | 'closed',
    notes: '',
    lines: [] as Array<{
        article_id: number | null;
        quantity: string;
        unit_price: string;
    }>,
});

// Reactive line display state (includes vat_rate + article_name for live totals)
const lineDetails = ref<OrderLineForm[]>([]);

// Shadcn Select bridge (string ↔ number/null)
const supplierIdString = computed({
    get: () => (form.supplier_id !== null ? String(form.supplier_id) : ''),
    set: (v: string) => {
        form.supplier_id = v ? parseInt(v, 10) : null;
    },
});

const customerOrderIdString = computed({
    get: () =>
        form.customer_order_id !== null ? String(form.customer_order_id) : '',
    set: (v: string) => {
        form.customer_order_id = v ? parseInt(v, 10) : null;
    },
});

// ─── Line item helpers ────────────────────────────────────────────────────────

function addLine() {
    lineDetails.value.push({
        article_id: null,
        quantity: '1',
        unit_price: '0.00',
        vat_rate: 0,
        article_name: '',
    });
    syncFormLines();
}

function removeLine(index: number) {
    lineDetails.value.splice(index, 1);
    syncFormLines();
}

function onArticleChange(index: number, value: string) {
    const articleId = value ? parseInt(value, 10) : null;
    const article = props.articles.find((a) => a.id === articleId) ?? null;
    if (article) {
        lineDetails.value[index].article_id = article.id;
        lineDetails.value[index].unit_price = String(article.price);
        lineDetails.value[index].vat_rate = article.vat_rate;
        lineDetails.value[index].article_name = article.name;
    } else {
        lineDetails.value[index].article_id = null;
        lineDetails.value[index].unit_price = '0.00';
        lineDetails.value[index].vat_rate = 0;
        lineDetails.value[index].article_name = '';
    }
    syncFormLines();
}

function onFieldChange(
    index: number,
    field: 'quantity' | 'unit_price',
    value: string,
) {
    (lineDetails.value[index] as Record<string, unknown>)[field] = value;
    syncFormLines();
}

function syncFormLines() {
    form.lines = lineDetails.value.map((l) => ({
        article_id: l.article_id,
        quantity: l.quantity,
        unit_price: l.unit_price,
    }));
}

// ─── Live totals ──────────────────────────────────────────────────────────────

const computedTotals = computed(() => {
    const byVat: Record<
        number,
        { rate: number; base: number; vatAmount: number }
    > = {};
    let subExVat = 0;
    let total = 0;

    for (const line of lineDetails.value) {
        const qty = parseFloat(line.quantity) || 0;
        const price = parseFloat(line.unit_price) || 0;
        const rate = line.vat_rate || 0;
        const base = qty * price;
        const vatAmt = base * (rate / 100);
        const lineTotal = base + vatAmt;

        subExVat += base;
        total += lineTotal;

        if (!byVat[rate]) byVat[rate] = { rate, base: 0, vatAmount: 0 };
        byVat[rate].base += base;
        byVat[rate].vatAmount += vatAmt;
    }

    return {
        subExVat,
        total,
        byVat: Object.values(byVat).sort((a, b) => a.rate - b.rate),
    };
});

function lineTotal(line: OrderLineForm): number {
    const qty = parseFloat(line.quantity) || 0;
    const price = parseFloat(line.unit_price) || 0;
    return qty * price * (1 + line.vat_rate / 100);
}

// ─── Form open / close ────────────────────────────────────────────────────────

const today = new Date().toISOString().split('T')[0];

function openCreate() {
    editingId.value = null;
    form.reset();
    form.order_date = today;
    form.status = 'draft';
    lineDetails.value = [];
    form.lines = [];
    form.clearErrors();
    addLine();
    sheetOpen.value = true;
}

function openEdit(order: OrderRow) {
    editingId.value = order.id;
    form.order_date = order.order_date;
    form.supplier_id = order.supplier_id;
    form.customer_order_id = order.customer_order_id;
    form.status = order.status;
    form.notes = order.notes ?? '';
    form.clearErrors();

    lineDetails.value = order.lines.map((l) => ({
        article_id: l.article_id,
        quantity: String(l.quantity),
        unit_price: String(l.unit_price),
        vat_rate: l.article?.vat_rate ?? 0,
        article_name: l.article?.name ?? '',
    }));
    syncFormLines();
    sheetOpen.value = true;
}

function closeSheet() {
    sheetOpen.value = false;
}

function submit() {
    const url = editingId.value
        ? SupplierOrderController.update.url({ order: editingId.value })
        : SupplierOrderController.store.url();
    const method = editingId.value ? 'put' : 'post';

    form[method](url, { onSuccess: closeSheet });
}

// ─── Delete ───────────────────────────────────────────────────────────────────

const deleteDialogOpen = ref(false);
const deletingId = ref<number | null>(null);
const isDeleting = ref(false);

function confirmDelete(id: number) {
    deletingId.value = id;
    deleteDialogOpen.value = true;
}

function executeDelete() {
    if (!deletingId.value) return;
    isDeleting.value = true;
    router.delete(
        SupplierOrderController.destroy.url({ order: deletingId.value }),
        {
            onSuccess: () => {
                deleteDialogOpen.value = false;
                deletingId.value = null;
                isDeleting.value = false;
            },
            onError: () => {
                isDeleting.value = false;
            },
        },
    );
}

// ─── Formatting helpers ───────────────────────────────────────────────────────

const EUR = new Intl.NumberFormat('pt-PT', {
    style: 'currency',
    currency: 'EUR',
});
function fmtEur(val: number) {
    return EUR.format(val);
}
function fmtNum(n: number): string {
    return new Intl.NumberFormat('pt-PT').format(n);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Supplier Orders" />

        <div class="space-y-4 px-4 py-6">
            <div class="flex items-center justify-between">
                <Heading
                    variant="small"
                    title="Supplier Orders"
                    description="Manage purchase orders sent to suppliers."
                />
                <Button @click="openCreate">Add Order</Button>
            </div>

            <Alert
                v-if="flash.success"
                class="border-green-200 bg-green-50 text-green-800"
            >
                <AlertDescription>{{ flash.success }}</AlertDescription>
            </Alert>
            <Alert v-if="flash.error" variant="destructive">
                <AlertDescription>{{ flash.error }}</AlertDescription>
            </Alert>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-24">No.</TableHead>
                        <TableHead class="w-28">Date</TableHead>
                        <TableHead>Supplier</TableHead>
                        <TableHead class="w-36">Customer Order</TableHead>
                        <TableHead class="w-32 text-right">Total</TableHead>
                        <TableHead class="w-24">Status</TableHead>
                        <TableHead class="w-36 text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="!props.orders.data.length" :colspan="7">
                        No supplier orders found.
                    </TableEmpty>
                    <TableRow
                        v-for="order in props.orders.data"
                        :key="order.id"
                    >
                        <TableCell
                            class="font-mono text-sm text-muted-foreground"
                        >
                            #{{ String(order.number).padStart(5, '0') }}
                        </TableCell>
                        <TableCell class="text-sm">{{
                            order.order_date
                        }}</TableCell>
                        <TableCell class="font-medium">{{
                            order.supplier?.name ?? '—'
                        }}</TableCell>
                        <TableCell class="text-sm text-muted-foreground">
                            <span v-if="order.customer_order">
                                #{{
                                    String(
                                        order.customer_order.number,
                                    ).padStart(5, '0')
                                }}
                            </span>
                            <span v-else class="italic">—</span>
                        </TableCell>
                        <TableCell class="text-right tabular-nums">
                            {{ fmtEur(order.total_amount) }}
                        </TableCell>
                        <TableCell>
                            <Badge
                                :variant="
                                    order.status === 'closed'
                                        ? 'default'
                                        : 'secondary'
                                "
                            >
                                {{ order.status }}
                            </Badge>
                        </TableCell>
                        <TableCell class="space-x-1 text-right">
                            <!-- PDF Download -->
                            <a
                                :href="
                                    SupplierOrderController.downloadPdf.url({
                                        order: order.id,
                                    })
                                "
                                target="_blank"
                                class="inline-flex h-8 items-center justify-center rounded-md px-3 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground"
                                >PDF</a
                            >
                            <Button
                                variant="ghost"
                                size="sm"
                                @click="openEdit(order)"
                                >Edit</Button
                            >
                            <Button
                                variant="ghost"
                                size="sm"
                                class="text-destructive hover:text-destructive"
                                @click="confirmDelete(order.id)"
                                >Delete</Button
                            >
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <!-- ── Create / Edit Sheet ─────────────────────────────────────── -->
        <Sheet v-model:open="sheetOpen">
            <SheetContent
                class="flex flex-col overflow-hidden p-0 sm:max-w-4xl"
            >
                <SheetHeader class="flex-shrink-0 border-b px-6 py-4">
                    <SheetTitle
                        >{{ editingId ? 'Edit' : 'New' }} Supplier
                        Order</SheetTitle
                    >
                </SheetHeader>

                <div class="flex-1 space-y-6 overflow-y-auto px-6 py-4">
                    <!-- ── Header fields ────────────────────────────── -->
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Supplier -->
                        <div class="col-span-2 grid gap-2">
                            <Label for="so-supplier"
                                >Supplier
                                <span class="text-destructive">*</span></Label
                            >
                            <Select v-model="supplierIdString">
                                <SelectTrigger id="so-supplier">
                                    <SelectValue
                                        placeholder="Select supplier"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="s in props.suppliers"
                                        :key="s.id"
                                        :value="String(s.id)"
                                        >{{ s.name }}</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.supplier_id" />
                        </div>

                        <!-- Order Date -->
                        <div class="grid gap-2">
                            <Label for="so-date"
                                >Order Date
                                <span class="text-destructive">*</span></Label
                            >
                            <Input
                                id="so-date"
                                v-model="form.order_date"
                                type="date"
                            />
                            <InputError :message="form.errors.order_date" />
                        </div>

                        <!-- Status -->
                        <div class="grid gap-2">
                            <Label for="so-status">Status</Label>
                            <Select v-model="form.status">
                                <SelectTrigger id="so-status">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="draft">Draft</SelectItem>
                                    <SelectItem value="closed"
                                        >Closed</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.status" />
                        </div>

                        <!-- Customer Order (optional link) -->
                        <div class="col-span-2 grid gap-2">
                            <Label for="so-customer-order"
                                >Customer Order (optional)</Label
                            >
                            <Select v-model="customerOrderIdString">
                                <SelectTrigger id="so-customer-order">
                                    <SelectValue placeholder="— None —" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">— None —</SelectItem>
                                    <SelectItem
                                        v-for="co in props.customerOrders"
                                        :key="co.id"
                                        :value="String(co.id)"
                                        >#{{
                                            String(co.number).padStart(5, '0')
                                        }}
                                        <span class="ml-1 text-muted-foreground"
                                            >({{ co.status }})</span
                                        >
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError
                                :message="form.errors.customer_order_id"
                            />
                        </div>

                        <!-- Notes -->
                        <div class="col-span-2 grid gap-2">
                            <Label for="so-notes">Notes</Label>
                            <textarea
                                id="so-notes"
                                v-model="form.notes"
                                rows="2"
                                class="flex w-full resize-none rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                            />
                        </div>
                    </div>

                    <!-- ── Line Items ────────────────────────────────── -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold">Line Items</h3>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="addLine"
                            >
                                + Add Line
                            </Button>
                        </div>
                        <InputError :message="form.errors.lines" />

                        <div class="overflow-auto rounded-md border">
                            <table class="w-full text-sm">
                                <thead class="bg-muted">
                                    <tr>
                                        <th
                                            class="w-56 px-3 py-2 text-left font-medium text-muted-foreground"
                                        >
                                            Article
                                        </th>
                                        <th
                                            class="w-20 px-3 py-2 text-right font-medium text-muted-foreground"
                                        >
                                            Qty
                                        </th>
                                        <th
                                            class="w-28 px-3 py-2 text-right font-medium text-muted-foreground"
                                        >
                                            Unit Price
                                        </th>
                                        <th
                                            class="w-14 px-3 py-2 text-right font-medium text-muted-foreground"
                                        >
                                            VAT%
                                        </th>
                                        <th
                                            class="w-28 px-3 py-2 text-right font-medium text-muted-foreground"
                                        >
                                            Line Total
                                        </th>
                                        <th class="w-8"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="lineDetails.length === 0">
                                        <td
                                            colspan="6"
                                            class="px-3 py-4 text-center text-xs text-muted-foreground italic"
                                        >
                                            No lines yet — click "+ Add Line" to
                                            start.
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="(line, idx) in lineDetails"
                                        :key="idx"
                                        class="border-t"
                                    >
                                        <!-- Article Select -->
                                        <td class="px-2 py-1.5">
                                            <Select
                                                :model-value="
                                                    line.article_id !== null
                                                        ? String(
                                                              line.article_id,
                                                          )
                                                        : ''
                                                "
                                                @update:model-value="
                                                    (v) =>
                                                        onArticleChange(
                                                            idx,
                                                            v as string,
                                                        )
                                                "
                                            >
                                                <SelectTrigger
                                                    class="h-8 text-xs"
                                                >
                                                    <SelectValue
                                                        placeholder="Select article"
                                                    />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem
                                                        v-for="a in props.articles"
                                                        :key="a.id"
                                                        :value="String(a.id)"
                                                    >
                                                        {{ a.reference }} —
                                                        {{ a.name }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                :message="
                                                    (
                                                        form.errors as Record<
                                                            string,
                                                            string
                                                        >
                                                    )[`lines.${idx}.article_id`]
                                                "
                                            />
                                        </td>

                                        <!-- Quantity -->
                                        <td class="px-2 py-1.5">
                                            <Input
                                                :model-value="line.quantity"
                                                type="number"
                                                step="0.01"
                                                min="0.01"
                                                class="h-8 text-right text-xs"
                                                @input="
                                                    (e: Event) =>
                                                        onFieldChange(
                                                            idx,
                                                            'quantity',
                                                            (
                                                                e.target as HTMLInputElement
                                                            ).value,
                                                        )
                                                "
                                            />
                                        </td>

                                        <!-- Unit Price -->
                                        <td class="px-2 py-1.5">
                                            <Input
                                                :model-value="line.unit_price"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                class="h-8 text-right text-xs"
                                                @input="
                                                    (e: Event) =>
                                                        onFieldChange(
                                                            idx,
                                                            'unit_price',
                                                            (
                                                                e.target as HTMLInputElement
                                                            ).value,
                                                        )
                                                "
                                            />
                                        </td>

                                        <!-- VAT Rate (read-only) -->
                                        <td
                                            class="px-3 py-1.5 text-right text-xs text-muted-foreground tabular-nums"
                                        >
                                            {{ fmtNum(line.vat_rate) }}%
                                        </td>

                                        <!-- Line Total -->
                                        <td
                                            class="px-3 py-1.5 text-right text-xs tabular-nums"
                                        >
                                            {{ fmtEur(lineTotal(line)) }}
                                        </td>

                                        <!-- Remove -->
                                        <td class="px-2 py-1.5">
                                            <button
                                                type="button"
                                                class="text-xs font-medium text-destructive hover:text-destructive/80"
                                                @click="removeLine(idx)"
                                            >
                                                ✕
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ── Live Totals ─────────────────────────────── -->
                    <div v-if="lineDetails.length > 0" class="flex justify-end">
                        <div class="w-64 space-y-1 text-sm">
                            <div
                                class="flex justify-between text-muted-foreground"
                            >
                                <span>Subtotal (ex-VAT)</span>
                                <span class="tabular-nums">{{
                                    fmtEur(computedTotals.subExVat)
                                }}</span>
                            </div>
                            <div
                                v-for="vb in computedTotals.byVat"
                                :key="vb.rate"
                                class="flex justify-between text-xs text-muted-foreground"
                            >
                                <span>VAT {{ fmtNum(vb.rate) }}%</span>
                                <span class="tabular-nums">{{
                                    fmtEur(vb.vatAmount)
                                }}</span>
                            </div>
                            <div
                                class="flex justify-between border-t pt-1 font-semibold"
                            >
                                <span>Total (inc. VAT)</span>
                                <span class="tabular-nums">{{
                                    fmtEur(computedTotals.total)
                                }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <SheetFooter class="flex-shrink-0 border-t px-6 py-4">
                    <Button variant="outline" @click="closeSheet"
                        >Cancel</Button
                    >
                    <Button :disabled="form.processing" @click="submit">
                        {{ editingId ? 'Update' : 'Create' }}
                    </Button>
                </SheetFooter>
            </SheetContent>
        </Sheet>

        <!-- ── Delete Confirmation ─────────────────────────────────────── -->
        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete Supplier Order</DialogTitle>
                </DialogHeader>
                <p class="text-sm text-muted-foreground">
                    Are you sure you want to delete this supplier order? This
                    action cannot be undone.
                </p>
                <DialogFooter>
                    <Button variant="outline" @click="deleteDialogOpen = false"
                        >Cancel</Button
                    >
                    <Button
                        variant="destructive"
                        :disabled="isDeleting"
                        @click="executeDelete"
                    >
                        {{ isDeleting ? 'Deleting…' : 'Delete' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
