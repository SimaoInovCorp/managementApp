<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CustomerAccountController from '@/actions/App/Http/Controllers/CustomerAccountController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
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
import { index as customerAccountsIndex } from '@/routes/financial/customer_accounts';
import type { BreadcrumbItem } from '@/types';

type EntityOption = { id: number; name: string };

type EntryRow = {
    id: number;
    entity_id: number;
    entity: { id: number; name: string } | null;
    description: string | null;
    debit: number;
    credit: number;
    date: string;
    running_balance: number | null;
    created_at: string;
};

const props = defineProps<{
    entries: { data: EntryRow[] };
    clients: EntityOption[];
    selectedEntityId: number | null;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Financial', href: '#' },
    { title: 'Customer Accounts', href: customerAccountsIndex() },
];

const page = usePage();
const flash = computed(
    () =>
        page.props.flash as { success?: string | null; error?: string | null },
);

// ─── Entity filter ────────────────────────────────────────────────────────────

const filterEntityId = ref<string>(
    props.selectedEntityId ? String(props.selectedEntityId) : '',
);

function applyFilter() {
    const params: Record<string, string> = {};
    if (filterEntityId.value) params.entity_id = filterEntityId.value;
    router.get(
        CustomerAccountController.index.url(params),
        {},
        { preserveState: true },
    );
}

// ─── Sheet state (store only — append-only ledger) ────────────────────────────

const sheetOpen = ref(false);

const form = useForm({
    entity_id: null as number | null,
    description: '',
    debit: '0.00',
    credit: '0.00',
    date: new Date().toISOString().split('T')[0],
});

const entityIdString = computed({
    get: () => (form.entity_id !== null ? String(form.entity_id) : ''),
    set: (v: string) => {
        form.entity_id = v ? parseInt(v, 10) : null;
    },
});

function openCreate() {
    form.reset();
    form.date = new Date().toISOString().split('T')[0];
    form.debit = '0.00';
    form.credit = '0.00';
    form.clearErrors();
    sheetOpen.value = true;
}

function closeSheet() {
    sheetOpen.value = false;
}

function submit() {
    form.post(CustomerAccountController.store.url(), { onSuccess: closeSheet });
}

// ─── Formatting ───────────────────────────────────────────────────────────────

const EUR = new Intl.NumberFormat('pt-PT', {
    style: 'currency',
    currency: 'EUR',
});
function fmtEur(val: number) {
    return EUR.format(val);
}

function runningBalanceClass(val: number | null): string {
    if (val === null) return '';
    return val >= 0
        ? 'text-green-700 font-semibold'
        : 'text-red-600 font-semibold';
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Customer Accounts" />

        <div class="space-y-4 px-4 py-6">
            <div class="flex items-center justify-between">
                <Heading
                    variant="small"
                    title="Customer Current Accounts"
                    description="Append-only ledger of debits and credits per client."
                />
                <Button @click="openCreate">Add Entry</Button>
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

            <!-- Filter bar -->
            <div class="flex items-end gap-3">
                <div class="grid w-64 gap-1">
                    <Label for="filter-entity">Filter by Client</Label>
                    <Select v-model="filterEntityId">
                        <SelectTrigger id="filter-entity"
                            ><SelectValue placeholder="All clients"
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All clients</SelectItem>
                            <SelectItem
                                v-for="c in props.clients"
                                :key="c.id"
                                :value="String(c.id)"
                                >{{ c.name }}</SelectItem
                            >
                        </SelectContent>
                    </Select>
                </div>
                <Button variant="outline" @click="applyFilter">Filter</Button>
            </div>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-28">Date</TableHead>
                        <TableHead>Client</TableHead>
                        <TableHead>Description</TableHead>
                        <TableHead class="w-32 text-right">Debit</TableHead>
                        <TableHead class="w-32 text-right">Credit</TableHead>
                        <TableHead class="w-36 text-right"
                            >Running Balance</TableHead
                        >
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="!props.entries.data.length" :colspan="6">
                        No ledger entries found.
                    </TableEmpty>
                    <TableRow
                        v-for="entry in props.entries.data"
                        :key="entry.id"
                    >
                        <TableCell class="text-sm">{{ entry.date }}</TableCell>
                        <TableCell class="font-medium">{{
                            entry.entity?.name ?? '—'
                        }}</TableCell>
                        <TableCell class="text-sm text-muted-foreground">{{
                            entry.description ?? '—'
                        }}</TableCell>
                        <TableCell class="text-right text-red-600 tabular-nums">
                            <span v-if="entry.debit > 0">{{
                                fmtEur(entry.debit)
                            }}</span>
                            <span v-else class="text-muted-foreground">—</span>
                        </TableCell>
                        <TableCell
                            class="text-right text-green-700 tabular-nums"
                        >
                            <span v-if="entry.credit > 0">{{
                                fmtEur(entry.credit)
                            }}</span>
                            <span v-else class="text-muted-foreground">—</span>
                        </TableCell>
                        <TableCell
                            class="text-right tabular-nums"
                            :class="runningBalanceClass(entry.running_balance)"
                        >
                            {{
                                entry.running_balance !== null
                                    ? fmtEur(entry.running_balance)
                                    : '—'
                            }}
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <!-- ── Add Entry Sheet ─────────────────────────────────────────── -->
        <Sheet v-model:open="sheetOpen">
            <SheetContent class="flex flex-col overflow-hidden p-0 sm:max-w-md">
                <SheetHeader class="flex-shrink-0 border-b px-6 py-4">
                    <SheetTitle>New Ledger Entry</SheetTitle>
                </SheetHeader>

                <div
                    v-if="sheetOpen"
                    class="flex-1 space-y-4 overflow-y-auto px-6 py-4"
                >
                    <div class="grid gap-2">
                        <Label for="ca-entity"
                            >Client
                            <span class="text-destructive">*</span></Label
                        >
                        <Select v-model="entityIdString">
                            <SelectTrigger id="ca-entity"
                                ><SelectValue placeholder="Select client"
                            /></SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="c in props.clients"
                                    :key="c.id"
                                    :value="String(c.id)"
                                    >{{ c.name }}</SelectItem
                                >
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.entity_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="ca-date"
                            >Date <span class="text-destructive">*</span></Label
                        >
                        <Input id="ca-date" v-model="form.date" type="date" />
                        <InputError :message="form.errors.date" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="ca-description">Description</Label>
                        <Input
                            id="ca-description"
                            v-model="form.description"
                            placeholder="e.g. Payment received"
                        />
                        <InputError :message="form.errors.description" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="ca-debit">Debit (€)</Label>
                            <Input
                                id="ca-debit"
                                v-model="form.debit"
                                type="number"
                                step="0.01"
                                min="0"
                            />
                            <InputError :message="form.errors.debit" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="ca-credit">Credit (€)</Label>
                            <Input
                                id="ca-credit"
                                v-model="form.credit"
                                type="number"
                                step="0.01"
                                min="0"
                            />
                            <InputError :message="form.errors.credit" />
                        </div>
                    </div>
                </div>

                <SheetFooter class="flex-shrink-0 border-t px-6 py-4">
                    <Button variant="outline" @click="closeSheet"
                        >Cancel</Button
                    >
                    <Button :disabled="form.processing" @click="submit"
                        >Add Entry</Button
                    >
                </SheetFooter>
            </SheetContent>
        </Sheet>
    </AppLayout>
</template>
