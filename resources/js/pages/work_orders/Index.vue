<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import WorkOrderController from '@/actions/App/Http/Controllers/WorkOrderController';
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
import { index as workOrdersIndex } from '@/routes/work_orders';
import type { BreadcrumbItem } from '@/types';

// ─── Types ────────────────────────────────────────────────────────────────────

type EntityOption = { id: number; name: string };

type OrderRow = {
    id: number;
    number: number;
    date: string;
    client_id: number;
    client: { id: number; name: string } | null;
    description: string | null;
    status: 'draft' | 'closed';
    created_at: string;
};

// ─── Props ────────────────────────────────────────────────────────────────────

const props = defineProps<{
    orders: { data: OrderRow[] };
    clients: EntityOption[];
}>();

// ─── Breadcrumbs ──────────────────────────────────────────────────────────────

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Work Orders', href: workOrdersIndex() },
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
    date: '',
    client_id: null as number | null,
    description: '',
    status: 'draft' as 'draft' | 'closed',
});

// Shadcn Select bridge (string ↔ number)
const clientIdString = computed({
    get: () => (form.client_id !== null ? String(form.client_id) : ''),
    set: (v: string) => {
        form.client_id = v ? parseInt(v, 10) : null;
    },
});

// ─── Form open / close ────────────────────────────────────────────────────────

const today = new Date().toISOString().split('T')[0];

function openCreate() {
    editingId.value = null;
    form.reset();
    form.date = today;
    form.status = 'draft';
    form.clearErrors();
    sheetOpen.value = true;
}

function openEdit(order: OrderRow) {
    editingId.value = order.id;
    form.date = order.date;
    form.client_id = order.client_id;
    form.description = order.description ?? '';
    form.status = order.status;
    form.clearErrors();
    sheetOpen.value = true;
}

function closeSheet() {
    sheetOpen.value = false;
}

function submit() {
    const url = editingId.value
        ? WorkOrderController.update.url({ order: editingId.value })
        : WorkOrderController.store.url();
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
        WorkOrderController.destroy.url({ order: deletingId.value }),
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
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Work Orders" />

        <div class="space-y-4 px-4 py-6">
            <div class="flex items-center justify-between">
                <Heading
                    variant="small"
                    title="Work Orders"
                    description="Manage work orders assigned to clients."
                />
                <Button @click="openCreate">Add Work Order</Button>
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
                        <TableHead>Client</TableHead>
                        <TableHead>Description</TableHead>
                        <TableHead class="w-24">Status</TableHead>
                        <TableHead class="w-32 text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="!props.orders.data.length" :colspan="6">
                        No work orders found.
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
                        <TableCell class="text-sm">{{ order.date }}</TableCell>
                        <TableCell class="font-medium">{{
                            order.client?.name ?? '—'
                        }}</TableCell>
                        <TableCell
                            class="max-w-xs truncate text-sm text-muted-foreground"
                        >
                            {{ order.description ?? '—' }}
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
            <SheetContent class="flex flex-col overflow-hidden p-0 sm:max-w-lg">
                <SheetHeader class="flex-shrink-0 border-b px-6 py-4">
                    <SheetTitle
                        >{{ editingId ? 'Edit' : 'New' }} Work Order</SheetTitle
                    >
                </SheetHeader>

                <div
                    v-if="sheetOpen"
                    class="flex-1 space-y-4 overflow-y-auto px-6 py-4"
                >
                    <!-- Client -->
                    <div class="grid gap-2">
                        <Label for="wo-client"
                            >Client
                            <span class="text-destructive">*</span></Label
                        >
                        <Select v-model="clientIdString">
                            <SelectTrigger id="wo-client">
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

                    <!-- Date -->
                    <div class="grid gap-2">
                        <Label for="wo-date"
                            >Date <span class="text-destructive">*</span></Label
                        >
                        <Input id="wo-date" v-model="form.date" type="date" />
                        <InputError :message="form.errors.date" />
                    </div>

                    <!-- Status -->
                    <div class="grid gap-2">
                        <Label for="wo-status">Status</Label>
                        <Select v-model="form.status">
                            <SelectTrigger id="wo-status">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="draft">Draft</SelectItem>
                                <SelectItem value="closed">Closed</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.status" />
                    </div>

                    <!-- Description -->
                    <div class="grid gap-2">
                        <Label for="wo-desc">Description</Label>
                        <textarea
                            id="wo-desc"
                            v-model="form.description"
                            rows="4"
                            placeholder="Describe the work to be performed…"
                            class="flex w-full resize-none rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                        />
                        <InputError :message="form.errors.description" />
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
                    <DialogTitle>Delete Work Order</DialogTitle>
                </DialogHeader>
                <p class="text-sm text-muted-foreground">
                    Are you sure you want to delete this work order? This action
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
