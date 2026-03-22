<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ProposalController from '@/actions/App/Http/Controllers/ProposalController';
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
import { index as proposalsIndex } from '@/routes/proposals';
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

type ProposalLineForm = {
    article_id: number | null;
    supplier_id: number | null;
    quantity: string;
    unit_price: string;
    cost_price: string;
    // derived display fields
    vat_rate: number;
    article_name: string;
};

type ProposalLineData = {
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
    supplier_id: number | null;
    supplier: { id: number; name: string } | null;
    quantity: number;
    unit_price: number;
    cost_price: number | null;
    sort_order: number;
};

type ProposalRow = {
    id: number;
    number: number;
    proposal_date: string;
    validity_date: string;
    client_id: number;
    client: { id: number; name: string } | null;
    total_amount: number;
    status: 'draft' | 'closed';
    notes: string | null;
    lines: ProposalLineData[];
    has_order: boolean;
    created_at: string;
};

// ─── Props ────────────────────────────────────────────────────────────────────

const props = defineProps<{
    proposals: { data: ProposalRow[] };
    clients: EntityOption[];
    suppliers: EntityOption[];
    articles: ArticleOption[];
}>();

// ─── Breadcrumbs ──────────────────────────────────────────────────────────────

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Proposals', href: proposalsIndex() },
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
    proposal_date: '',
    client_id: null as number | null,
    validity_date: '',
    status: 'draft' as 'draft' | 'closed',
    notes: '',
    lines: [] as Array<{
        article_id: number | null;
        supplier_id: number | null;
        quantity: string;
        unit_price: string;
        cost_price: string;
    }>,
});

// Reactive line display state (includes vat_rate and article_name for live totals)
const lineDetails = ref<ProposalLineForm[]>([]);

// Shadcn Select bridges (string ↔ number)
const clientIdString = computed({
    get: () => (form.client_id !== null ? String(form.client_id) : ''),
    set: (v: string) => {
        form.client_id = v ? parseInt(v, 10) : null;
    },
});

// ─── Line item helpers ────────────────────────────────────────────────────────

function addLine() {
    lineDetails.value.push({
        article_id: null,
        supplier_id: null,
        quantity: '1',
        unit_price: '0.00',
        cost_price: '',
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

function onSupplierChange(index: number, value: string) {
    lineDetails.value[index].supplier_id = value ? parseInt(value, 10) : null;
    syncFormLines();
}

function onFieldChange(
    index: number,
    field: 'quantity' | 'unit_price' | 'cost_price',
    value: string,
) {
    (lineDetails.value[index] as Record<string, unknown>)[field] = value;
    syncFormLines();
}

/** Keep form.lines in sync with lineDetails */
function syncFormLines() {
    form.lines = lineDetails.value.map((l) => ({
        article_id: l.article_id,
        supplier_id: l.supplier_id,
        quantity: l.quantity,
        unit_price: l.unit_price,
        cost_price: l.cost_price || '',
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

function lineTotal(line: ProposalLineForm): number {
    const qty = parseFloat(line.quantity) || 0;
    const price = parseFloat(line.unit_price) || 0;
    return qty * price * (1 + line.vat_rate / 100);
}

// ─── Form open / close ────────────────────────────────────────────────────────

const today = new Date().toISOString().split('T')[0];

function openCreate() {
    editingId.value = null;
    form.reset();
    form.proposal_date = today;
    form.validity_date = datePlusDays(today, 30);
    form.status = 'draft';
    lineDetails.value = [];
    form.lines = [];
    form.clearErrors();
    addLine(); // start with one empty line
    sheetOpen.value = true;
}

function openEdit(proposal: ProposalRow) {
    editingId.value = proposal.id;
    form.proposal_date = proposal.proposal_date;
    form.client_id = proposal.client_id;
    form.validity_date = proposal.validity_date;
    form.status = proposal.status;
    form.notes = proposal.notes ?? '';
    form.clearErrors();

    // Rebuild lineDetails from existing lines
    lineDetails.value = proposal.lines.map((l) => ({
        article_id: l.article_id,
        supplier_id: l.supplier_id,
        quantity: String(l.quantity),
        unit_price: String(l.unit_price),
        cost_price: l.cost_price !== null ? String(l.cost_price) : '',
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
        ? ProposalController.update.url({ proposal: editingId.value })
        : ProposalController.store.url();
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
        ProposalController.destroy.url({ proposal: deletingId.value }),
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

// ─── Convert to Order ─────────────────────────────────────────────────────────

const convertDialogOpen = ref(false);
const convertingId = ref<number | null>(null);
const isConverting = ref(false);

function openConvert(id: number) {
    convertingId.value = id;
    convertDialogOpen.value = true;
}

function executeConvert() {
    if (!convertingId.value) return;
    isConverting.value = true;
    router.post(
        ProposalController.convertToOrder.url({ proposal: convertingId.value }),
        {},
        {
            onSuccess: () => {
                convertDialogOpen.value = false;
                convertingId.value = null;
                isConverting.value = false;
            },
            onError: () => {
                isConverting.value = false;
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

function datePlusDays(date: string, days: number): string {
    const d = new Date(date);
    d.setDate(d.getDate() + days);
    return d.toISOString().split('T')[0];
}

function fmtNum(n: number): string {
    return new Intl.NumberFormat('pt-PT').format(n);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Proposals" />

        <div class="space-y-4 px-4 py-6">
            <div class="flex items-center justify-between">
                <Heading
                    variant="small"
                    title="Proposals"
                    description="Manage commercial proposals issued to clients."
                />
                <Button @click="openCreate">Add Proposal</Button>
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
                        <TableHead class="w-28">Valid Until</TableHead>
                        <TableHead>Client</TableHead>
                        <TableHead class="w-32 text-right">Total</TableHead>
                        <TableHead class="w-24">Status</TableHead>
                        <TableHead class="w-44 text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty
                        v-if="!props.proposals.data.length"
                        :colspan="7"
                    >
                        No proposals found.
                    </TableEmpty>
                    <TableRow
                        v-for="proposal in props.proposals.data"
                        :key="proposal.id"
                        :class="{ 'opacity-60': proposal.has_order }"
                    >
                        <TableCell
                            class="font-mono text-sm text-muted-foreground"
                        >
                            #{{ String(proposal.number).padStart(5, '0') }}
                        </TableCell>
                        <TableCell class="text-sm">{{
                            proposal.proposal_date
                        }}</TableCell>
                        <TableCell class="text-sm">{{
                            proposal.validity_date
                        }}</TableCell>
                        <TableCell class="font-medium">{{
                            proposal.client?.name ?? '—'
                        }}</TableCell>
                        <TableCell class="text-right tabular-nums">
                            {{ fmtEur(proposal.total_amount) }}
                        </TableCell>
                        <TableCell>
                            <Badge
                                :variant="
                                    proposal.status === 'closed'
                                        ? 'default'
                                        : 'secondary'
                                "
                            >
                                {{ proposal.status }}
                            </Badge>
                        </TableCell>
                        <TableCell class="space-x-1 text-right">
                            <!-- PDF Download -->
                            <a
                                :href="
                                    ProposalController.downloadPdf.url({
                                        proposal: proposal.id,
                                    })
                                "
                                target="_blank"
                                class="inline-flex h-8 items-center justify-center rounded-md px-3 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground"
                                >PDF</a
                            >
                            <!-- Convert to Order (only for closed proposals without an order yet) -->
                            <Button
                                v-if="
                                    proposal.status === 'closed' &&
                                    !proposal.has_order
                                "
                                variant="ghost"
                                size="sm"
                                class="text-green-700 hover:text-green-700"
                                @click="openConvert(proposal.id)"
                                >Order</Button
                            >
                            <span
                                v-else-if="proposal.has_order"
                                class="px-2 text-xs text-muted-foreground"
                                >Converted</span
                            >
                            <Button
                                variant="ghost"
                                size="sm"
                                @click="openEdit(proposal)"
                                >Edit</Button
                            >
                            <Button
                                variant="ghost"
                                size="sm"
                                class="text-destructive hover:text-destructive"
                                @click="confirmDelete(proposal.id)"
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
                        >{{ editingId ? 'Edit' : 'New' }} Proposal</SheetTitle
                    >
                </SheetHeader>

                <div class="flex-1 space-y-6 overflow-y-auto px-6 py-4">
                    <!-- ── Header fields ────────────────────────────── -->
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Client -->
                        <div class="col-span-2 grid gap-2">
                            <Label for="pr-client"
                                >Client
                                <span class="text-destructive">*</span></Label
                            >
                            <Select v-model="clientIdString">
                                <SelectTrigger id="pr-client">
                                    <SelectValue placeholder="Select client" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="c in props.clients"
                                        :key="c.id"
                                        :value="String(c.id)"
                                        >{{ c.name }}</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.client_id" />
                        </div>

                        <!-- Proposal Date -->
                        <div class="grid gap-2">
                            <Label for="pr-date"
                                >Proposal Date
                                <span class="text-destructive">*</span></Label
                            >
                            <Input
                                id="pr-date"
                                v-model="form.proposal_date"
                                type="date"
                            />
                            <InputError :message="form.errors.proposal_date" />
                        </div>

                        <!-- Validity Date -->
                        <div class="grid gap-2">
                            <Label for="pr-valid">Valid Until</Label>
                            <Input
                                id="pr-valid"
                                v-model="form.validity_date"
                                type="date"
                            />
                            <InputError :message="form.errors.validity_date" />
                        </div>

                        <!-- Status -->
                        <div class="grid gap-2">
                            <Label for="pr-status">Status</Label>
                            <Select v-model="form.status">
                                <SelectTrigger id="pr-status">
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

                        <!-- Notes -->
                        <div class="grid gap-2">
                            <Label for="pr-notes">Notes</Label>
                            <textarea
                                id="pr-notes"
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
                                            class="w-48 px-3 py-2 text-left font-medium text-muted-foreground"
                                        >
                                            Article
                                        </th>
                                        <th
                                            class="w-20 px-3 py-2 text-right font-medium text-muted-foreground"
                                        >
                                            Qty
                                        </th>
                                        <th
                                            class="w-24 px-3 py-2 text-right font-medium text-muted-foreground"
                                        >
                                            Unit Price
                                        </th>
                                        <th
                                            class="w-14 px-3 py-2 text-right font-medium text-muted-foreground"
                                        >
                                            VAT%
                                        </th>
                                        <th
                                            class="w-36 px-3 py-2 text-left font-medium text-muted-foreground"
                                        >
                                            Supplier
                                        </th>
                                        <th
                                            class="w-24 px-3 py-2 text-right font-medium text-muted-foreground"
                                        >
                                            Cost Price
                                        </th>
                                        <th
                                            class="w-24 px-3 py-2 text-right font-medium text-muted-foreground"
                                        >
                                            Line Total
                                        </th>
                                        <th class="w-8"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="lineDetails.length === 0">
                                        <td
                                            colspan="8"
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

                                        <!-- Supplier Select -->
                                        <td class="px-2 py-1.5">
                                            <Select
                                                :model-value="
                                                    line.supplier_id !== null
                                                        ? String(
                                                              line.supplier_id,
                                                          )
                                                        : ''
                                                "
                                                @update:model-value="
                                                    (v) =>
                                                        onSupplierChange(
                                                            idx,
                                                            v as string,
                                                        )
                                                "
                                            >
                                                <SelectTrigger
                                                    class="h-8 text-xs"
                                                >
                                                    <SelectValue
                                                        placeholder="— None —"
                                                    />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value=""
                                                        >— None —</SelectItem
                                                    >
                                                    <SelectItem
                                                        v-for="s in props.suppliers"
                                                        :key="s.id"
                                                        :value="String(s.id)"
                                                        >{{
                                                            s.name
                                                        }}</SelectItem
                                                    >
                                                </SelectContent>
                                            </Select>
                                        </td>

                                        <!-- Cost Price -->
                                        <td class="px-2 py-1.5">
                                            <Input
                                                :model-value="line.cost_price"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                placeholder="—"
                                                class="h-8 text-right text-xs"
                                                @input="
                                                    (e: Event) =>
                                                        onFieldChange(
                                                            idx,
                                                            'cost_price',
                                                            (
                                                                e.target as HTMLInputElement
                                                            ).value,
                                                        )
                                                "
                                            />
                                        </td>

                                        <!-- Line Total -->
                                        <td
                                            class="px-3 py-1.5 text-right text-xs font-medium tabular-nums"
                                        >
                                            {{ fmtEur(lineTotal(line)) }}
                                        </td>

                                        <!-- Remove -->
                                        <td class="px-1 py-1.5">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                class="h-7 w-7 p-0 text-destructive hover:text-destructive"
                                                @click="removeLine(idx)"
                                                >✕</Button
                                            >
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ── Live Totals ───────────────────────────────── -->
                    <div v-if="lineDetails.length > 0" class="flex justify-end">
                        <table class="w-56 text-sm">
                            <tbody>
                                <tr>
                                    <td
                                        class="py-0.5 pr-4 text-muted-foreground"
                                    >
                                        Subtotal (ex-VAT)
                                    </td>
                                    <td
                                        class="text-right font-medium tabular-nums"
                                    >
                                        {{ fmtEur(computedTotals.subExVat) }}
                                    </td>
                                </tr>
                                <tr
                                    v-for="vb in computedTotals.byVat"
                                    :key="vb.rate"
                                >
                                    <td
                                        class="py-0.5 pr-4 text-muted-foreground"
                                    >
                                        VAT {{ fmtNum(vb.rate) }}%
                                    </td>
                                    <td
                                        class="text-right font-medium tabular-nums"
                                    >
                                        {{ fmtEur(vb.vatAmount) }}
                                    </td>
                                </tr>
                                <tr class="border-t">
                                    <td class="pt-1.5 pr-4 font-bold">Total</td>
                                    <td
                                        class="pt-1.5 text-right font-bold tabular-nums"
                                    >
                                        {{ fmtEur(computedTotals.total) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <SheetFooter
                    class="flex flex-shrink-0 justify-end gap-2 border-t px-6 py-4"
                >
                    <Button type="button" variant="outline" @click="closeSheet"
                        >Cancel</Button
                    >
                    <Button
                        type="button"
                        :disabled="form.processing"
                        @click="submit"
                    >
                        {{ editingId ? 'Save Changes' : 'Create Proposal' }}
                    </Button>
                </SheetFooter>
            </SheetContent>
        </Sheet>

        <!-- ── Convert to Order Dialog ────────────────────────────────── -->
        <Dialog v-model:open="convertDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Convert to Customer Order</DialogTitle>
                </DialogHeader>
                <p class="text-sm text-muted-foreground">
                    This will create a new Customer Order (draft) copying all
                    lines from this proposal. The proposal will be marked as
                    converted.
                </p>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="convertDialogOpen = false"
                        >Cancel</Button
                    >
                    <Button
                        type="button"
                        :disabled="isConverting"
                        @click="executeConvert"
                    >
                        Create Order
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- ── Delete Confirmation Dialog ─────────────────────────────── -->
        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete Proposal</DialogTitle>
                </DialogHeader>
                <p class="text-sm text-muted-foreground">
                    Are you sure you want to delete this proposal? All line
                    items will be removed. This action cannot be undone.
                </p>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="deleteDialogOpen = false"
                        >Cancel</Button
                    >
                    <Button
                        type="button"
                        variant="destructive"
                        :disabled="isDeleting"
                        @click="executeDelete"
                    >
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
