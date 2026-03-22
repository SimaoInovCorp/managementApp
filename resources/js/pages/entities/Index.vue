<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref } from 'vue';
import EntityController from '@/actions/App/Http/Controllers/EntityController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { lookup as viesLookup } from '@/routes/vies';
import type { BreadcrumbItem } from '@/types';

// ─── Types ────────────────────────────────────────────────────────────────────

type CountryOption = { id: number; name: string; code: string };

type EntityRow = {
    id: number;
    type: 'client' | 'supplier' | 'both';
    number: number;
    nif: string | null;
    name: string;
    address: string | null;
    postal_code: string | null;
    locality: string | null;
    country_id: number | null;
    country: CountryOption | null;
    phone: string | null;
    mobile: string | null;
    website: string | null;
    email: string | null;
    gdpr_consent: boolean;
    notes: string | null;
    status: 'active' | 'inactive';
    created_at: string;
};

// ─── Props ────────────────────────────────────────────────────────────────────

const props = defineProps<{
    entities: { data: EntityRow[] };
    countries: CountryOption[];
    currentType: 'client' | 'supplier' | 'all';
}>();

// ─── Breadcrumbs ──────────────────────────────────────────────────────────────

const pageTitle = computed(() =>
    props.currentType === 'client'
        ? 'Clients'
        : props.currentType === 'supplier'
          ? 'Suppliers'
          : 'Entities',
);

const breadcrumbItems: BreadcrumbItem[] = [{ title: pageTitle.value }];

// ─── Flash & page ─────────────────────────────────────────────────────────────

const page = usePage();
const flash = computed(
    () =>
        page.props.flash as { success?: string | null; error?: string | null },
);

// ─── Sheet state ──────────────────────────────────────────────────────────────

const sheetOpen = ref(false);
const editingId = ref<number | null>(null);

// Type checkboxes
const isClient = ref(false);
const isSupplier = ref(false);

const typeValue = computed<'client' | 'supplier' | 'both'>(() => {
    if (isClient.value && isSupplier.value) return 'both';
    if (isSupplier.value) return 'supplier';
    return 'client';
});

// The form — mirroring EntityResource fields
const form = useForm({
    type: 'client' as 'client' | 'supplier' | 'both',
    nif: '',
    name: '',
    address: '',
    postal_code: '',
    locality: '',
    country_id: null as number | null,
    phone: '',
    mobile: '',
    website: '',
    email: '',
    gdpr_consent: false,
    notes: '',
    status: 'active' as 'active' | 'inactive',
});

const countryIdString = computed({
    get: () => (form.country_id !== null ? String(form.country_id) : ''),
    set: (val: string) => {
        form.country_id = val ? parseInt(val, 10) : null;
    },
});

// ─── VIES lookup ──────────────────────────────────────────────────────────────

const isLookingUp = ref(false);
const viesResult = ref<{ name?: string; address?: string } | null>(null);

async function lookupVies() {
    const nif = form.nif.trim();
    if (nif.length < 9) return;
    isLookingUp.value = true;
    viesResult.value = null;

    try {
        const res = await axios.get(viesLookup.url({ query: { vat: nif } }));
        const data = res.data as {
            valid: boolean;
            name?: string;
            address?: string;
        };
        if (data.valid && data.name) {
            viesResult.value = { name: data.name, address: data.address };
        }
    } catch {
        // Silently ignore — VIES is an optional helper, not critical
    } finally {
        isLookingUp.value = false;
    }
}

function applyVies() {
    if (!viesResult.value) return;
    if (viesResult.value.name && !form.name) {
        form.name = viesResult.value.name;
    }
    if (viesResult.value.address && !form.address) {
        form.address = viesResult.value.address;
    }
    viesResult.value = null;
}

// ─── Form open / close ────────────────────────────────────────────────────────

function openCreate() {
    editingId.value = null;
    isClient.value = props.currentType !== 'supplier';
    isSupplier.value = props.currentType !== 'client';
    form.reset();
    form.clearErrors();
    viesResult.value = null;
    sheetOpen.value = true;
}

function openEdit(entity: EntityRow) {
    editingId.value = entity.id;
    isClient.value = entity.type === 'client' || entity.type === 'both';
    isSupplier.value = entity.type === 'supplier' || entity.type === 'both';
    form.nif = entity.nif ?? '';
    form.name = entity.name;
    form.address = entity.address ?? '';
    form.postal_code = entity.postal_code ?? '';
    form.locality = entity.locality ?? '';
    form.country_id = entity.country_id;
    form.phone = entity.phone ?? '';
    form.mobile = entity.mobile ?? '';
    form.website = entity.website ?? '';
    form.email = entity.email ?? '';
    form.gdpr_consent = entity.gdpr_consent;
    form.notes = entity.notes ?? '';
    form.status = entity.status;
    form.clearErrors();
    viesResult.value = null;
    sheetOpen.value = true;
}

function closeSheet() {
    sheetOpen.value = false;
}

function submit() {
    form.type = typeValue.value;

    if (editingId.value) {
        form.put(EntityController.update.url({ entity: editingId.value }), {
            onSuccess: closeSheet,
        });
    } else {
        form.post(EntityController.store.url(), {
            onSuccess: closeSheet,
        });
    }
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
    router.delete(EntityController.destroy.url({ entity: deletingId.value }), {
        onSuccess: () => {
            deleteDialogOpen.value = false;
            deletingId.value = null;
            isDeleting.value = false;
        },
        onError: () => {
            isDeleting.value = false;
        },
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="pageTitle" />

        <div class="space-y-4 px-4 py-6">
            <div class="flex items-center justify-between">
                <Heading
                    variant="small"
                    :title="pageTitle"
                    :description="
                        currentType === 'client'
                            ? 'Manage your clients and their details.'
                            : currentType === 'supplier'
                              ? 'Manage your suppliers and their details.'
                              : 'Manage entities.'
                    "
                />
                <Button @click="openCreate"
                    >Add
                    {{
                        currentType === 'supplier' ? 'Supplier' : 'Client'
                    }}</Button
                >
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
                        <TableHead class="w-24">#</TableHead>
                        <TableHead class="w-28">NIF</TableHead>
                        <TableHead>Name</TableHead>
                        <TableHead class="w-32">Phone</TableHead>
                        <TableHead class="w-32">Mobile</TableHead>
                        <TableHead class="w-28">Status</TableHead>
                        <TableHead class="w-32 text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="!props.entities.data.length" :colspan="7">
                        No
                        {{
                            currentType === 'client'
                                ? 'clients'
                                : currentType === 'supplier'
                                  ? 'suppliers'
                                  : 'entities'
                        }}
                        found.
                    </TableEmpty>
                    <TableRow
                        v-for="entity in props.entities.data"
                        :key="entity.id"
                    >
                        <TableCell
                            class="font-mono text-sm text-muted-foreground"
                            >{{
                                String(entity.number).padStart(4, '0')
                            }}</TableCell
                        >
                        <TableCell class="font-mono text-sm">{{
                            entity.nif ?? '—'
                        }}</TableCell>
                        <TableCell class="font-medium">{{
                            entity.name
                        }}</TableCell>
                        <TableCell>{{ entity.phone ?? '—' }}</TableCell>
                        <TableCell>{{ entity.mobile ?? '—' }}</TableCell>
                        <TableCell>
                            <Badge
                                :variant="
                                    entity.status === 'active'
                                        ? 'default'
                                        : 'secondary'
                                "
                            >
                                {{ entity.status }}
                            </Badge>
                        </TableCell>
                        <TableCell class="space-x-1 text-right">
                            <Button
                                variant="ghost"
                                size="sm"
                                @click="openEdit(entity)"
                                >Edit</Button
                            >
                            <Button
                                variant="ghost"
                                size="sm"
                                class="text-destructive hover:text-destructive"
                                @click="confirmDelete(entity.id)"
                                >Delete</Button
                            >
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <!-- ── Create / Edit Sheet ─────────────────────────────────────────── -->
        <Sheet v-model:open="sheetOpen">
            <SheetContent
                class="flex flex-col overflow-hidden p-0 sm:max-w-[600px]"
            >
                <SheetHeader class="flex-shrink-0 border-b px-6 py-4">
                    <SheetTitle
                        >{{ editingId ? 'Edit' : 'Add' }}
                        {{
                            currentType === 'supplier' ? 'Supplier' : 'Client'
                        }}</SheetTitle
                    >
                </SheetHeader>

                <div
                    v-if="sheetOpen"
                    class="flex-1 space-y-4 overflow-y-auto px-6 py-4"
                >
                    <!-- Type checkboxes -->
                    <div class="flex gap-6">
                        <Label class="flex cursor-pointer items-center gap-2">
                            <Checkbox
                                :checked="isClient"
                                @update:checked="
                                    (v: boolean | 'indeterminate') => {
                                        isClient = !!v;
                                    }
                                "
                            />
                            Client
                        </Label>
                        <Label class="flex cursor-pointer items-center gap-2">
                            <Checkbox
                                :checked="isSupplier"
                                @update:checked="
                                    (v: boolean | 'indeterminate') => {
                                        isSupplier = !!v;
                                    }
                                "
                            />
                            Supplier
                        </Label>
                    </div>

                    <!-- NIF with VIES lookup -->
                    <div class="grid gap-2">
                        <Label for="ent-nif">VAT / NIF</Label>
                        <div class="relative">
                            <Input
                                id="ent-nif"
                                v-model="form.nif"
                                class="pr-20"
                                autocomplete="off"
                                placeholder="e.g. PT507957547 or 507957547"
                                @blur="lookupVies"
                            />
                            <span
                                v-if="isLookingUp"
                                class="absolute top-1/2 right-3 -translate-y-1/2 animate-pulse text-xs text-muted-foreground"
                            >
                                Searching…
                            </span>
                        </div>
                        <!-- VIES auto-fill suggestion -->
                        <div
                            v-if="viesResult"
                            class="flex items-center justify-between rounded-md border bg-green-50 px-3 py-2 text-sm text-green-800"
                        >
                            <span
                                >Found:
                                <strong>{{ viesResult.name }}</strong></span
                            >
                            <Button
                                size="sm"
                                variant="ghost"
                                class="h-7 px-2 text-green-700"
                                @click="applyVies"
                                >Auto-fill</Button
                            >
                        </div>
                        <InputError :message="form.errors.nif" />
                    </div>

                    <!-- Name -->
                    <div class="grid gap-2">
                        <Label for="ent-name"
                            >Name <span class="text-destructive">*</span></Label
                        >
                        <Input
                            id="ent-name"
                            v-model="form.name"
                            autocomplete="off"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <!-- Address -->
                    <div class="grid gap-2">
                        <Label for="ent-address">Address</Label>
                        <Input
                            id="ent-address"
                            v-model="form.address"
                            autocomplete="off"
                        />
                        <InputError :message="form.errors.address" />
                    </div>

                    <!-- Postal Code + Locality -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="ent-postal">Postal Code</Label>
                            <Input
                                id="ent-postal"
                                v-model="form.postal_code"
                                autocomplete="off"
                                placeholder="XXXX-XXX"
                                maxlength="8"
                            />
                            <InputError :message="form.errors.postal_code" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="ent-locality">Locality</Label>
                            <Input
                                id="ent-locality"
                                v-model="form.locality"
                                autocomplete="off"
                            />
                            <InputError :message="form.errors.locality" />
                        </div>
                    </div>

                    <!-- Country -->
                    <div class="grid gap-2">
                        <Label for="ent-country">Country</Label>
                        <Select v-model="countryIdString">
                            <SelectTrigger id="ent-country">
                                <SelectValue placeholder="Select country" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="country in props.countries"
                                    :key="country.id"
                                    :value="String(country.id)"
                                >
                                    {{ country.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.country_id" />
                    </div>

                    <!-- Phone + Mobile -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="ent-phone">Phone</Label>
                            <Input
                                id="ent-phone"
                                v-model="form.phone"
                                type="tel"
                                autocomplete="off"
                            />
                            <InputError :message="form.errors.phone" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="ent-mobile">Mobile</Label>
                            <Input
                                id="ent-mobile"
                                v-model="form.mobile"
                                type="tel"
                                autocomplete="off"
                            />
                            <InputError :message="form.errors.mobile" />
                        </div>
                    </div>

                    <!-- Website + Email -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="ent-website">Website</Label>
                            <Input
                                id="ent-website"
                                v-model="form.website"
                                type="url"
                                autocomplete="off"
                                placeholder="https://"
                            />
                            <InputError :message="form.errors.website" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="ent-email">Email</Label>
                            <Input
                                id="ent-email"
                                v-model="form.email"
                                type="email"
                                autocomplete="off"
                            />
                            <InputError :message="form.errors.email" />
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="grid gap-2">
                        <Label for="ent-notes">Notes</Label>
                        <textarea
                            id="ent-notes"
                            v-model="form.notes"
                            rows="3"
                            class="flex min-h-[80px] w-full resize-none rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        />
                        <InputError :message="form.errors.notes" />
                    </div>

                    <!-- GDPR + Status -->
                    <div class="flex items-center gap-8">
                        <Label class="flex cursor-pointer items-center gap-2">
                            <Checkbox
                                :checked="form.gdpr_consent"
                                @update:checked="
                                    (v: boolean | 'indeterminate') => {
                                        form.gdpr_consent = !!v;
                                    }
                                "
                            />
                            GDPR Consent given
                        </Label>
                        <div class="ml-auto grid gap-1.5">
                            <Label for="ent-status">Status</Label>
                            <Select v-model="form.status">
                                <SelectTrigger id="ent-status" class="w-32">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="active"
                                        >Active</SelectItem
                                    >
                                    <SelectItem value="inactive"
                                        >Inactive</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </div>
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
                        {{ editingId ? 'Save Changes' : 'Add' }}
                    </Button>
                </SheetFooter>
            </SheetContent>
        </Sheet>

        <!-- ── Delete Confirmation Dialog ─────────────────────────────────── -->
        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle
                        >Delete
                        {{
                            currentType === 'supplier' ? 'Supplier' : 'Client'
                        }}</DialogTitle
                    >
                </DialogHeader>
                <p class="text-sm text-muted-foreground">
                    Are you sure you want to delete this entity? This action
                    cannot be undone.
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
                        >Delete</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
