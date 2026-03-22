<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import BankAccountController from '@/actions/App/Http/Controllers/BankAccountController';
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
import { index as bankAccountsIndex } from '@/routes/financial/bank_accounts';
import type { BreadcrumbItem } from '@/types';

type AccountRow = {
    id: number;
    name: string;
    iban: string;
    iban_masked: string;
    bic: string | null;
    balance: number;
    status: 'active' | 'inactive';
};

const props = defineProps<{
    accounts: { data: AccountRow[] };
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Financial', href: '#' },
    { title: 'Bank Accounts', href: bankAccountsIndex() },
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
    name: '',
    iban: '',
    bic: '',
    balance: '0',
    status: 'active' as 'active' | 'inactive',
});

function openCreate() {
    editingId.value = null;
    form.reset();
    form.status = 'active';
    form.balance = '0';
    form.clearErrors();
    sheetOpen.value = true;
}

function openEdit(account: AccountRow) {
    editingId.value = account.id;
    form.name = account.name;
    form.iban = account.iban;
    form.bic = account.bic ?? '';
    form.balance = String(account.balance);
    form.status = account.status;
    form.clearErrors();
    sheetOpen.value = true;
}

function closeSheet() {
    sheetOpen.value = false;
}

function submit() {
    const url = editingId.value
        ? BankAccountController.update.url({ account: editingId.value })
        : BankAccountController.store.url();
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
        BankAccountController.destroy.url({ account: deletingId.value }),
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

const EUR = new Intl.NumberFormat('pt-PT', {
    style: 'currency',
    currency: 'EUR',
});
function fmtEur(val: number) {
    return EUR.format(val);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Bank Accounts" />

        <div class="space-y-4 px-4 py-6">
            <div class="flex items-center justify-between">
                <Heading
                    variant="small"
                    title="Bank Accounts"
                    description="Manage company bank accounts with encrypted IBAN storage."
                />
                <Button @click="openCreate">Add Account</Button>
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
                        <TableHead>IBAN</TableHead>
                        <TableHead class="w-32">BIC</TableHead>
                        <TableHead class="w-36 text-right">Balance</TableHead>
                        <TableHead class="w-24">Status</TableHead>
                        <TableHead class="w-32 text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="!props.accounts.data.length" :colspan="6">
                        No bank accounts found.
                    </TableEmpty>
                    <TableRow
                        v-for="account in props.accounts.data"
                        :key="account.id"
                    >
                        <TableCell class="font-medium">{{
                            account.name
                        }}</TableCell>
                        <TableCell
                            class="font-mono text-sm text-muted-foreground"
                        >
                            {{ account.iban_masked }}
                        </TableCell>
                        <TableCell class="text-sm">{{
                            account.bic ?? '—'
                        }}</TableCell>
                        <TableCell class="text-right font-medium tabular-nums">
                            {{ fmtEur(account.balance) }}
                        </TableCell>
                        <TableCell>
                            <Badge
                                :variant="
                                    account.status === 'active'
                                        ? 'default'
                                        : 'secondary'
                                "
                            >
                                {{ account.status }}
                            </Badge>
                        </TableCell>
                        <TableCell class="space-x-1 text-right">
                            <Button
                                variant="ghost"
                                size="sm"
                                @click="openEdit(account)"
                                >Edit</Button
                            >
                            <Button
                                variant="ghost"
                                size="sm"
                                class="text-destructive hover:text-destructive"
                                @click="confirmDelete(account.id)"
                                >Delete</Button
                            >
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <!-- ── Create / Edit Sheet ─────────────────────────────────────── -->
        <Sheet v-model:open="sheetOpen">
            <SheetContent class="flex flex-col overflow-hidden p-0 sm:max-w-md">
                <SheetHeader class="flex-shrink-0 border-b px-6 py-4">
                    <SheetTitle
                        >{{ editingId ? 'Edit' : 'New' }} Bank
                        Account</SheetTitle
                    >
                </SheetHeader>

                <div
                    v-if="sheetOpen"
                    class="flex-1 space-y-4 overflow-y-auto px-6 py-4"
                >
                    <div class="grid gap-2">
                        <Label for="ba-name"
                            >Account Name
                            <span class="text-destructive">*</span></Label
                        >
                        <Input
                            id="ba-name"
                            v-model="form.name"
                            placeholder="e.g. Main Operating Account"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="ba-iban"
                            >IBAN <span class="text-destructive">*</span></Label
                        >
                        <Input
                            id="ba-iban"
                            v-model="form.iban"
                            placeholder="PT50 0000 0000 0000 0000 0000 0"
                        />
                        <InputError :message="form.errors.iban" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="ba-bic">BIC / SWIFT</Label>
                        <Input
                            id="ba-bic"
                            v-model="form.bic"
                            placeholder="e.g. CGDIPTPL"
                        />
                        <InputError :message="form.errors.bic" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="ba-balance">Opening Balance (€)</Label>
                        <Input
                            id="ba-balance"
                            v-model="form.balance"
                            type="number"
                            step="0.01"
                            min="0"
                        />
                        <InputError :message="form.errors.balance" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="ba-status">Status</Label>
                        <Select v-model="form.status">
                            <SelectTrigger id="ba-status"
                                ><SelectValue
                            /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="active">Active</SelectItem>
                                <SelectItem value="inactive"
                                    >Inactive</SelectItem
                                >
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

        <!-- ── Delete Confirmation Dialog ─────────────────────────────── -->
        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete Bank Account</DialogTitle>
                </DialogHeader>
                <p class="text-sm text-muted-foreground">
                    Are you sure you want to delete this bank account? This
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
