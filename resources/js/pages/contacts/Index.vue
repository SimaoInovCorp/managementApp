<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ContactController from '@/actions/App/Http/Controllers/ContactController';
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
import type { BreadcrumbItem } from '@/types';

// ─── Types ────────────────────────────────────────────────────────────────────

type EntityOption = { id: number; name: string; type: string };
type RoleOption = { id: number; name: string };

type ContactRow = {
    id: number;
    number: number;
    entity_id: number;
    entity: EntityOption | null;
    first_name: string;
    last_name: string;
    role_id: number | null;
    role: RoleOption | null;
    phone: string | null;
    mobile: string | null;
    email: string | null;
    gdpr_consent: boolean;
    notes: string | null;
    status: 'active' | 'inactive';
    created_at: string;
};

// ─── Props ────────────────────────────────────────────────────────────────────

const props = defineProps<{
    contacts: { data: ContactRow[] };
    entities: EntityOption[];
    roles: RoleOption[];
    filters: { entity_id: number | null };
}>();

// ─── Breadcrumbs ──────────────────────────────────────────────────────────────

const breadcrumbItems: BreadcrumbItem[] = [{ title: 'Contacts' }];

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
    entity_id: null as number | null,
    first_name: '',
    last_name: '',
    role_id: null as number | null,
    phone: '',
    mobile: '',
    email: '',
    gdpr_consent: false,
    notes: '',
    status: 'active' as 'active' | 'inactive',
});

// Shadcn Select needs strings; convert to/from integer IDs
const entityIdString = computed({
    get: () => (form.entity_id !== null ? String(form.entity_id) : ''),
    set: (v: string) => {
        form.entity_id = v ? parseInt(v, 10) : null;
    },
});

const roleIdString = computed({
    get: () => (form.role_id !== null ? String(form.role_id) : ''),
    set: (v: string) => {
        form.role_id = v ? parseInt(v, 10) : null;
    },
});

// ─── Form open / close ────────────────────────────────────────────────────────

function openCreate() {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    // Pre-select entity if a filter is active
    if (props.filters.entity_id) {
        form.entity_id = props.filters.entity_id;
    }
    sheetOpen.value = true;
}

function openEdit(contact: ContactRow) {
    editingId.value = contact.id;
    form.entity_id = contact.entity_id;
    form.first_name = contact.first_name;
    form.last_name = contact.last_name;
    form.role_id = contact.role_id;
    form.phone = contact.phone ?? '';
    form.mobile = contact.mobile ?? '';
    form.email = contact.email ?? '';
    form.gdpr_consent = contact.gdpr_consent;
    form.notes = contact.notes ?? '';
    form.status = contact.status;
    form.clearErrors();
    sheetOpen.value = true;
}

function closeSheet() {
    sheetOpen.value = false;
}

function submit() {
    if (editingId.value) {
        form.put(ContactController.update.url({ contact: editingId.value }), {
            onSuccess: closeSheet,
        });
    } else {
        form.post(ContactController.store.url(), {
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
    router.delete(
        ContactController.destroy.url({ contact: deletingId.value }),
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
        <Head title="Contacts" />

        <div class="space-y-4 px-4 py-6">
            <div class="flex items-center justify-between">
                <Heading
                    variant="small"
                    title="Contacts"
                    description="Manage people linked to your clients and suppliers."
                />
                <Button @click="openCreate">Add Contact</Button>
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
                        <TableHead>Name</TableHead>
                        <TableHead class="w-44">Entity</TableHead>
                        <TableHead class="w-36">Role</TableHead>
                        <TableHead class="w-32">Mobile</TableHead>
                        <TableHead class="w-44">Email</TableHead>
                        <TableHead class="w-24">Status</TableHead>
                        <TableHead class="w-28 text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="!props.contacts.data.length" :colspan="8">
                        No contacts found.
                    </TableEmpty>
                    <TableRow
                        v-for="contact in props.contacts.data"
                        :key="contact.id"
                    >
                        <TableCell
                            class="font-mono text-sm text-muted-foreground"
                        >
                            {{ String(contact.number).padStart(4, '0') }}
                        </TableCell>
                        <TableCell class="font-medium">
                            {{ contact.first_name }} {{ contact.last_name }}
                        </TableCell>
                        <TableCell class="text-sm text-muted-foreground">
                            {{ contact.entity?.name ?? '—' }}
                        </TableCell>
                        <TableCell class="text-sm">{{
                            contact.role?.name ?? '—'
                        }}</TableCell>
                        <TableCell>{{ contact.mobile ?? '—' }}</TableCell>
                        <TableCell class="text-sm">{{
                            contact.email ?? '—'
                        }}</TableCell>
                        <TableCell>
                            <Badge
                                :variant="
                                    contact.status === 'active'
                                        ? 'default'
                                        : 'secondary'
                                "
                            >
                                {{ contact.status }}
                            </Badge>
                        </TableCell>
                        <TableCell class="space-x-1 text-right">
                            <Button
                                variant="ghost"
                                size="sm"
                                @click="openEdit(contact)"
                                >Edit</Button
                            >
                            <Button
                                variant="ghost"
                                size="sm"
                                class="text-destructive hover:text-destructive"
                                @click="confirmDelete(contact.id)"
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
                class="flex flex-col overflow-hidden p-0 sm:max-w-[520px]"
            >
                <SheetHeader class="flex-shrink-0 border-b px-6 py-4">
                    <SheetTitle
                        >{{ editingId ? 'Edit' : 'Add' }} Contact</SheetTitle
                    >
                </SheetHeader>

                <div class="flex-1 space-y-4 overflow-y-auto px-6 py-4">
                    <!-- Entity -->
                    <div class="grid gap-2">
                        <Label for="ct-entity"
                            >Entity
                            <span class="text-destructive">*</span></Label
                        >
                        <Select v-model="entityIdString">
                            <SelectTrigger id="ct-entity">
                                <SelectValue placeholder="Select entity" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="entity in props.entities"
                                    :key="entity.id"
                                    :value="String(entity.id)"
                                >
                                    {{ entity.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.entity_id" />
                    </div>

                    <!-- First Name + Last Name -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="ct-first"
                                >First Name
                                <span class="text-destructive">*</span></Label
                            >
                            <Input
                                id="ct-first"
                                v-model="form.first_name"
                                autocomplete="off"
                            />
                            <InputError :message="form.errors.first_name" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="ct-last"
                                >Last Name
                                <span class="text-destructive">*</span></Label
                            >
                            <Input
                                id="ct-last"
                                v-model="form.last_name"
                                autocomplete="off"
                            />
                            <InputError :message="form.errors.last_name" />
                        </div>
                    </div>

                    <!-- Role -->
                    <div class="grid gap-2">
                        <Label for="ct-role">Role</Label>
                        <Select v-model="roleIdString">
                            <SelectTrigger id="ct-role">
                                <SelectValue placeholder="Select role" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">— No role —</SelectItem>
                                <SelectItem
                                    v-for="role in props.roles"
                                    :key="role.id"
                                    :value="String(role.id)"
                                >
                                    {{ role.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.role_id" />
                    </div>

                    <!-- Phone + Mobile -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="ct-phone">Phone</Label>
                            <Input
                                id="ct-phone"
                                v-model="form.phone"
                                type="tel"
                                autocomplete="off"
                            />
                            <InputError :message="form.errors.phone" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="ct-mobile">Mobile</Label>
                            <Input
                                id="ct-mobile"
                                v-model="form.mobile"
                                type="tel"
                                autocomplete="off"
                            />
                            <InputError :message="form.errors.mobile" />
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="grid gap-2">
                        <Label for="ct-email">Email</Label>
                        <Input
                            id="ct-email"
                            v-model="form.email"
                            type="email"
                            autocomplete="off"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <!-- Notes -->
                    <div class="grid gap-2">
                        <Label for="ct-notes">Notes</Label>
                        <textarea
                            id="ct-notes"
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
                            <Label for="ct-status">Status</Label>
                            <Select v-model="form.status">
                                <SelectTrigger id="ct-status" class="w-32">
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
                        {{ editingId ? 'Save Changes' : 'Add Contact' }}
                    </Button>
                </SheetFooter>
            </SheetContent>
        </Sheet>

        <!-- ── Delete Confirmation Dialog ─────────────────────────────────── -->
        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete Contact</DialogTitle>
                </DialogHeader>
                <p class="text-sm text-muted-foreground">
                    Are you sure you want to delete this contact? This action
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
