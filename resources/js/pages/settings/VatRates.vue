<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import VatRateController from '@/actions/App/Http/Controllers/Settings/VatRateController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
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
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { index as vatRatesIndex } from '@/routes/settings/config/vat-rates';
import type { BreadcrumbItem } from '@/types';

type VatRate = { id: number; name: string; rate: number };

const props = defineProps<{ vatRates: { data: VatRate[] } }>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'VAT Rates', href: vatRatesIndex() },
];

const page = usePage();
const flash = computed(
    () =>
        page.props.flash as { success?: string | null; error?: string | null },
);

const dialogOpen = ref(false);
const deleteDialogOpen = ref(false);
const editingId = ref<number | null>(null);
const deletingId = ref<number | null>(null);
const isDeleting = ref(false);

const form = useForm({ name: '', rate: '' });

function openCreate() {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function openEdit(item: VatRate) {
    editingId.value = item.id;
    form.name = item.name;
    form.rate = String(item.rate);
    form.clearErrors();
    dialogOpen.value = true;
}

function closeDialog() {
    dialogOpen.value = false;
    editingId.value = null;
    form.reset();
    form.clearErrors();
}

function submit() {
    if (editingId.value) {
        form.put(VatRateController.update.url({ vatRate: editingId.value }), {
            onSuccess: closeDialog,
        });
    } else {
        form.post(VatRateController.store.url(), {
            onSuccess: closeDialog,
        });
    }
}

function confirmDelete(id: number) {
    deletingId.value = id;
    deleteDialogOpen.value = true;
}

function executeDelete() {
    if (!deletingId.value) return;
    isDeleting.value = true;
    router.delete(
        VatRateController.destroy.url({ vatRate: deletingId.value }),
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
        <Head title="VAT Rates" />

        <SettingsLayout>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <Heading
                        variant="small"
                        title="VAT Rates"
                        description="Manage VAT rates used on articles and invoices."
                    />
                    <Button @click="openCreate">Add VAT Rate</Button>
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
                            <TableHead>Name</TableHead>
                            <TableHead class="w-28">Rate (%)</TableHead>
                            <TableHead class="w-32 text-right"
                                >Actions</TableHead
                            >
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableEmpty
                            v-if="!props.vatRates.data.length"
                            :colspan="3"
                            >No VAT rates found.</TableEmpty
                        >
                        <TableRow
                            v-for="vatRate in props.vatRates.data"
                            :key="vatRate.id"
                        >
                            <TableCell>{{ vatRate.name }}</TableCell>
                            <TableCell>{{ vatRate.rate }}%</TableCell>
                            <TableCell class="space-x-1 text-right">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="openEdit(vatRate)"
                                    >Edit</Button
                                >
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="text-destructive hover:text-destructive"
                                    @click="confirmDelete(vatRate.id)"
                                    >Delete</Button
                                >
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Create / Edit Dialog -->
            <Dialog v-model:open="dialogOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{{
                            editingId ? 'Edit VAT Rate' : 'Add VAT Rate'
                        }}</DialogTitle>
                    </DialogHeader>
                    <form class="space-y-4" @submit.prevent="submit">
                        <div class="grid gap-2">
                            <Label for="name">Name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                autocomplete="off"
                                placeholder="e.g. Standard Rate"
                            />
                            <InputError :message="form.errors.name" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="rate">Rate (%)</Label>
                            <Input
                                id="rate"
                                v-model="form.rate"
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                autocomplete="off"
                                placeholder="e.g. 23"
                            />
                            <InputError :message="form.errors.rate" />
                        </div>
                    </form>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeDialog"
                            >Cancel</Button
                        >
                        <Button
                            type="button"
                            :disabled="form.processing"
                            @click="submit"
                        >
                            {{ editingId ? 'Save Changes' : 'Add VAT Rate' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Delete Confirmation Dialog -->
            <Dialog v-model:open="deleteDialogOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete VAT Rate</DialogTitle>
                    </DialogHeader>
                    <p class="text-sm text-muted-foreground">
                        Are you sure you want to delete this VAT rate? This
                        action cannot be undone.
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
        </SettingsLayout>
    </AppLayout>
</template>
