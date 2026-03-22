<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import SupplierInvoiceController from '@/actions/App/Http/Controllers/SupplierInvoiceController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
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
import { index as supplierInvoicesIndex } from '@/routes/financial/supplier_invoices';
import type { BreadcrumbItem } from '@/types';

// ─── Types ────────────────────────────────────────────────────────────────────

type EntityOption = { id: number; name: string };
type SupplierOrderOption = { id: number; number: number; status: string };

type InvoiceRow = {
    id: number;
    number: number;
    invoice_date: string;
    due_date: string;
    supplier_id: number;
    supplier_order_id: number | null;
    total_amount: number;
    document_path: string | null;
    payment_proof_path: string | null;
    status: 'pending' | 'paid';
    supplier: { id: number; name: string } | null;
    supplier_order: { id: number; number: number } | null;
    created_at: string;
};

const props = defineProps<{
    invoices: { data: InvoiceRow[] };
    suppliers: EntityOption[];
    supplierOrders: SupplierOrderOption[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Financial', href: '#' },
    { title: 'Supplier Invoices', href: supplierInvoicesIndex() },
];

const page = usePage();
const flash = computed(
    () =>
        page.props.flash as { success?: string | null; error?: string | null },
);

// ─── Sheet state ──────────────────────────────────────────────────────────────

const sheetOpen = ref(false);
const editingId = ref<number | null>(null);

const form = useForm({
    invoice_date: '',
    due_date: '',
    supplier_id: null as number | null,
    supplier_order_id: null as number | null,
    total_amount: '0.00',
    status: 'pending' as 'pending' | 'paid',
});

const supplierIdString = computed({
    get: () => (form.supplier_id !== null ? String(form.supplier_id) : ''),
    set: (v: string) => {
        form.supplier_id = v ? parseInt(v, 10) : null;
    },
});

const supplierOrderIdString = computed({
    get: () =>
        form.supplier_order_id !== null ? String(form.supplier_order_id) : '',
    set: (v: string) => {
        form.supplier_order_id = v ? parseInt(v, 10) : null;
    },
});

const today = new Date().toISOString().split('T')[0];
// Due date defaults to 30 days from invoice date
const defaultDueDate = computed(() => {
    const d = new Date();
    d.setDate(d.getDate() + 30);
    return d.toISOString().split('T')[0];
});

function openCreate() {
    editingId.value = null;
    form.reset();
    form.invoice_date = today;
    form.due_date = defaultDueDate.value;
    form.status = 'pending';
    form.total_amount = '0.00';
    form.clearErrors();
    sheetOpen.value = true;
}

function openEdit(invoice: InvoiceRow) {
    editingId.value = invoice.id;
    form.invoice_date = invoice.invoice_date;
    form.due_date = invoice.due_date;
    form.supplier_id = invoice.supplier_id;
    form.supplier_order_id = invoice.supplier_order_id;
    form.total_amount = String(invoice.total_amount);
    form.status = invoice.status;
    form.clearErrors();
    sheetOpen.value = true;
}

function closeSheet() {
    sheetOpen.value = false;
}

function submit() {
    const url = editingId.value
        ? SupplierInvoiceController.update.url({ invoice: editingId.value })
        : SupplierInvoiceController.store.url();
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
        SupplierInvoiceController.destroy.url({ invoice: deletingId.value }),
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

// ─── Payment Confirmation Dialog ──────────────────────────────────────────────

const payDialogOpen = ref(false);
const payingInvoice = ref<InvoiceRow | null>(null);
const paymentProofFile = ref<File | null>(null);
const sendEmail = ref(true);
const isProcessingPay = ref(false);

function openPayDialog(invoice: InvoiceRow) {
    payingInvoice.value = invoice;
    paymentProofFile.value = null;
    sendEmail.value = true;
    payDialogOpen.value = true;
}

function onProofFileChange(e: Event) {
    const target = e.target as HTMLInputElement;
    paymentProofFile.value = target.files?.[0] ?? null;
}

function confirmPayment() {
    if (!payingInvoice.value) return;
    isProcessingPay.value = true;

    const formData = new FormData();
    formData.append('_method', 'POST');
    formData.append('send_email', sendEmail.value ? '1' : '0');
    if (paymentProofFile.value) {
        formData.append('payment_proof', paymentProofFile.value);
    }

    router.post(
        SupplierInvoiceController.sendPaymentConfirmation.url({
            invoice: payingInvoice.value.id,
        }),
        formData,
        {
            forceFormData: true,
            onSuccess: () => {
                payDialogOpen.value = false;
                payingInvoice.value = null;
                isProcessingPay.value = false;
            },
            onError: () => {
                isProcessingPay.value = false;
            },
        },
    );
}

// ─── Formatting ───────────────────────────────────────────────────────────────

const EUR = new Intl.NumberFormat('pt-PT', {
    style: 'currency',
    currency: 'EUR',
});
function fmtEur(val: number) {
    return EUR.format(val);
}

function isOverdue(invoice: InvoiceRow): boolean {
    return (
        invoice.status === 'pending' && new Date(invoice.due_date) < new Date()
    );
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Supplier Invoices" />

        <div class="space-y-4 px-4 py-6">
            <div class="flex items-center justify-between">
                <Heading
                    variant="small"
                    title="Supplier Invoices"
                    description="Track invoices received from suppliers, including payment status."
                />
                <Button @click="openCreate">Add Invoice</Button>
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
                        <TableHead class="w-28">Due Date</TableHead>
                        <TableHead>Supplier</TableHead>
                        <TableHead class="w-32">Order</TableHead>
                        <TableHead class="w-32 text-right">Total</TableHead>
                        <TableHead class="w-24">Status</TableHead>
                        <TableHead class="w-40 text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="!props.invoices.data.length" :colspan="8">
                        No supplier invoices found.
                    </TableEmpty>
                    <TableRow
                        v-for="invoice in props.invoices.data"
                        :key="invoice.id"
                        :class="isOverdue(invoice) ? 'bg-red-50' : ''"
                    >
                        <TableCell
                            class="font-mono text-sm text-muted-foreground"
                        >
                            #{{ String(invoice.number).padStart(5, '0') }}
                        </TableCell>
                        <TableCell class="text-sm">{{
                            invoice.invoice_date
                        }}</TableCell>
                        <TableCell
                            class="text-sm"
                            :class="
                                isOverdue(invoice)
                                    ? 'font-semibold text-red-600'
                                    : ''
                            "
                        >
                            {{ invoice.due_date }}
                        </TableCell>
                        <TableCell class="font-medium">{{
                            invoice.supplier?.name ?? '—'
                        }}</TableCell>
                        <TableCell class="text-sm text-muted-foreground">
                            <span v-if="invoice.supplier_order">
                                #{{
                                    String(
                                        invoice.supplier_order.number,
                                    ).padStart(5, '0')
                                }}
                            </span>
                            <span v-else class="italic">—</span>
                        </TableCell>
                        <TableCell class="text-right tabular-nums">{{
                            fmtEur(invoice.total_amount)
                        }}</TableCell>
                        <TableCell>
                            <Badge
                                :variant="
                                    invoice.status === 'paid'
                                        ? 'default'
                                        : 'secondary'
                                "
                            >
                                {{ invoice.status }}
                            </Badge>
                        </TableCell>
                        <TableCell class="space-x-1 text-right">
                            <Button
                                v-if="invoice.status === 'pending'"
                                variant="outline"
                                size="sm"
                                class="border-green-300 text-green-700 hover:bg-green-50"
                                @click="openPayDialog(invoice)"
                                >Mark Paid</Button
                            >
                            <Button
                                variant="ghost"
                                size="sm"
                                @click="openEdit(invoice)"
                                >Edit</Button
                            >
                            <Button
                                variant="ghost"
                                size="sm"
                                class="text-destructive hover:text-destructive"
                                @click="confirmDelete(invoice.id)"
                                >Delete</Button
                            >
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <!-- ── Create / Edit Sheet ─────────────────────────────────────── -->
        <Sheet v-model:open="sheetOpen">
            <SheetContent class="flex flex-col overflow-hidden p-0 sm:max-w-lg">
                <SheetHeader class="flex-shrink-0 border-b px-6 py-4">
                    <SheetTitle
                        >{{ editingId ? 'Edit' : 'New' }} Supplier
                        Invoice</SheetTitle
                    >
                </SheetHeader>

                <div
                    v-if="sheetOpen"
                    class="flex-1 space-y-4 overflow-y-auto px-6 py-4"
                >
                    <!-- Supplier -->
                    <div class="grid gap-2">
                        <Label for="si-supplier"
                            >Supplier
                            <span class="text-destructive">*</span></Label
                        >
                        <Select v-model="supplierIdString">
                            <SelectTrigger id="si-supplier"
                                ><SelectValue placeholder="Select supplier"
                            /></SelectTrigger>
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

                    <!-- Dates -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="si-invoice-date"
                                >Invoice Date
                                <span class="text-destructive">*</span></Label
                            >
                            <Input
                                id="si-invoice-date"
                                v-model="form.invoice_date"
                                type="date"
                            />
                            <InputError :message="form.errors.invoice_date" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="si-due-date"
                                >Due Date
                                <span class="text-destructive">*</span></Label
                            >
                            <Input
                                id="si-due-date"
                                v-model="form.due_date"
                                type="date"
                            />
                            <InputError :message="form.errors.due_date" />
                        </div>
                    </div>

                    <!-- Total Amount -->
                    <div class="grid gap-2">
                        <Label for="si-total"
                            >Total Amount (€)
                            <span class="text-destructive">*</span></Label
                        >
                        <Input
                            id="si-total"
                            v-model="form.total_amount"
                            type="number"
                            step="0.01"
                            min="0"
                        />
                        <InputError :message="form.errors.total_amount" />
                    </div>

                    <!-- Linked Supplier Order (optional) -->
                    <div class="grid gap-2">
                        <Label for="si-order"
                            >Linked Supplier Order (optional)</Label
                        >
                        <Select v-model="supplierOrderIdString">
                            <SelectTrigger id="si-order"
                                ><SelectValue placeholder="— None —"
                            /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">— None —</SelectItem>
                                <SelectItem
                                    v-for="o in props.supplierOrders"
                                    :key="o.id"
                                    :value="String(o.id)"
                                    >#{{ String(o.number).padStart(5, '0') }}
                                    <span class="ml-1 text-muted-foreground"
                                        >({{ o.status }})</span
                                    >
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.supplier_order_id" />
                    </div>

                    <!-- Status -->
                    <div class="grid gap-2">
                        <Label for="si-status">Status</Label>
                        <Select v-model="form.status">
                            <SelectTrigger id="si-status"
                                ><SelectValue
                            /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="pending">Pending</SelectItem>
                                <SelectItem value="paid">Paid</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.status" />
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

        <!-- ── Payment Confirmation Dialog ────────────────────────────── -->
        <Dialog v-model:open="payDialogOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Confirm Payment</DialogTitle>
                    <DialogDescription>
                        Mark invoice
                        <strong
                            >#{{
                                payingInvoice
                                    ? String(payingInvoice.number).padStart(
                                          5,
                                          '0',
                                      )
                                    : ''
                            }}</strong
                        >
                        as paid and optionally send a confirmation email to the
                        supplier.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-2">
                    <!-- Payment proof upload -->
                    <div class="grid gap-2">
                        <Label for="pay-proof">Payment Proof (optional)</Label>
                        <Input
                            id="pay-proof"
                            type="file"
                            accept=".pdf,.jpg,.jpeg,.png"
                            @change="onProofFileChange"
                        />
                        <p class="text-xs text-muted-foreground">
                            PDF, JPG or PNG — max 10 MB
                        </p>
                    </div>

                    <!-- Send email toggle -->
                    <div class="flex items-center gap-3">
                        <input
                            id="pay-send-email"
                            v-model="sendEmail"
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300"
                        />
                        <Label for="pay-send-email" class="cursor-pointer">
                            Send payment confirmation email to supplier
                        </Label>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="payDialogOpen = false"
                        >Cancel</Button
                    >
                    <Button
                        class="bg-green-700 text-white hover:bg-green-800"
                        :disabled="isProcessingPay"
                        @click="confirmPayment"
                    >
                        {{
                            isProcessingPay ? 'Processing…' : 'Confirm Payment'
                        }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- ── Delete Confirmation Dialog ─────────────────────────────── -->
        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete Invoice</DialogTitle>
                </DialogHeader>
                <p class="text-sm text-muted-foreground">
                    Are you sure you want to delete this invoice? This action
                    cannot be undone.
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
