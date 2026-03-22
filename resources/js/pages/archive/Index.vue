<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import DigitalArchiveController from '@/actions/App/Http/Controllers/DigitalArchiveController';
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
import { index as archiveIndex } from '@/routes/archive';
import type { BreadcrumbItem } from '@/types';

// ─── Types ────────────────────────────────────────────────────────────────────

type EntityOption = { id: number; name: string };

type ArchiveRow = {
    id: number;
    name: string;
    category: string | null;
    entity_id: number | null;
    entity: { id: number; name: string } | null;
    description: string | null;
    uploaded_by: number;
    uploader: { id: number; name: string } | null;
    created_at: string;
};

defineProps<{
    archives: { data: ArchiveRow[] };
    entities: EntityOption[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Digital Archive', href: archiveIndex() },
];

const page = usePage();
const flash = computed(
    () =>
        page.props.flash as { success?: string | null; error?: string | null },
);

// ─── Upload Sheet ─────────────────────────────────────────────────────────────

const sheetOpen = ref(false);
const selectedFile = ref<File | null>(null);

const form = useForm({
    name: '',
    category: '',
    entity_id: null as number | null,
    description: '',
    file: null as File | null,
});

const entityIdString = computed({
    get: () => (form.entity_id !== null ? String(form.entity_id) : ''),
    set: (v: string) => {
        form.entity_id = v ? parseInt(v, 10) : null;
    },
});

function openUpload() {
    selectedFile.value = null;
    form.reset();
    form.clearErrors();
    sheetOpen.value = true;
}

function closeSheet() {
    sheetOpen.value = false;
}

function onFileChange(e: Event) {
    const target = e.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;
    selectedFile.value = file;

    // Auto-fill name from filename if not yet filled
    if (file && !form.name) {
        // Strip extension for the display name
        form.name = file.name.replace(/\.[^/.]+$/, '');
    }
}

function submit() {
    if (!selectedFile.value) return;

    const data = new FormData();
    data.append('file', selectedFile.value);
    data.append('name', form.name);
    data.append('category', form.category ?? '');
    if (form.entity_id) data.append('entity_id', String(form.entity_id));
    if (form.description) data.append('description', form.description);

    router.post(DigitalArchiveController.store.url(), data, {
        forceFormData: true,
        onSuccess: closeSheet,
        onError: (errors) => {
            Object.assign(form.errors, errors);
        },
    });
}

// ─── Delete ───────────────────────────────────────────────────────────────────

const deleteDialogOpen = ref(false);
const deletingId = ref<number | null>(null);
const deletingName = ref('');
const isDeleting = ref(false);

function confirmDelete(row: ArchiveRow) {
    deletingId.value = row.id;
    deletingName.value = row.name;
    deleteDialogOpen.value = true;
}

function executeDelete() {
    if (!deletingId.value) return;
    isDeleting.value = true;
    router.delete(
        DigitalArchiveController.destroy.url({ archive: deletingId.value }),
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

// ─── Download ─────────────────────────────────────────────────────────────────

function downloadFile(id: number) {
    window.location.href = DigitalArchiveController.show.url({ archive: id });
}

// ─── Formatting ───────────────────────────────────────────────────────────────

function formatDate(dt: string): string {
    return new Date(dt).toLocaleDateString('pt-PT');
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Digital Archive" />

        <div class="space-y-4 p-4">
            <div class="flex items-center justify-between">
                <Heading
                    title="Digital Archive"
                    description="Private documents stored outside the public directory."
                />
                <Button @click="openUpload">Upload File</Button>
            </div>

            <!-- Flash messages -->
            <Alert v-if="flash.success" variant="default">
                <AlertDescription>{{ flash.success }}</AlertDescription>
            </Alert>
            <Alert v-if="flash.error" variant="destructive">
                <AlertDescription>{{ flash.error }}</AlertDescription>
            </Alert>

            <!-- Table -->
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Name</TableHead>
                        <TableHead class="w-28">Category</TableHead>
                        <TableHead>Entity</TableHead>
                        <TableHead>Description</TableHead>
                        <TableHead>Uploaded By</TableHead>
                        <TableHead class="w-28">Date</TableHead>
                        <TableHead class="w-28 text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="!archives.data.length" :colspan="7">
                        No files in the archive yet.
                    </TableEmpty>
                    <TableRow v-for="row in archives.data" :key="row.id">
                        <TableCell class="font-medium">{{
                            row.name
                        }}</TableCell>
                        <TableCell>
                            <Badge v-if="row.category" variant="secondary">{{
                                row.category
                            }}</Badge>
                            <span v-else class="text-sm text-muted-foreground"
                                >—</span
                            >
                        </TableCell>
                        <TableCell>{{ row.entity?.name ?? '—' }}</TableCell>
                        <TableCell
                            class="max-w-xs truncate text-sm text-muted-foreground"
                        >
                            {{ row.description ?? '—' }}
                        </TableCell>
                        <TableCell>{{ row.uploader?.name ?? '—' }}</TableCell>
                        <TableCell>{{ formatDate(row.created_at) }}</TableCell>
                        <TableCell class="text-right">
                            <div class="flex justify-end gap-1">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="downloadFile(row.id)"
                                >
                                    Download
                                </Button>
                                <Button
                                    size="sm"
                                    variant="destructive"
                                    @click="confirmDelete(row)"
                                >
                                    Delete
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <!-- Upload Sheet -->
        <Sheet :open="sheetOpen" @update:open="(v) => !v && closeSheet()">
            <SheetContent class="overflow-y-auto sm:max-w-md">
                <SheetHeader>
                    <SheetTitle>Upload File</SheetTitle>
                </SheetHeader>

                <form class="mt-4 space-y-4" @submit.prevent="submit">
                    <!-- File picker -->
                    <div class="grid gap-1.5">
                        <Label for="file"
                            >File <span class="text-destructive">*</span></Label
                        >
                        <Input id="file" type="file" @change="onFileChange" />
                        <p class="text-xs text-muted-foreground">
                            Max 50 MB · PDF, Word, Excel, images, ZIP
                        </p>
                        <InputError :message="form.errors.file" />
                    </div>

                    <!-- Display name -->
                    <div class="grid gap-1.5">
                        <Label for="name"
                            >Display Name
                            <span class="text-destructive">*</span></Label
                        >
                        <Input
                            id="name"
                            v-model="form.name"
                            placeholder="Invoice Q1 2026"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <!-- Category -->
                    <div class="grid gap-1.5">
                        <Label for="category">Category</Label>
                        <Input
                            id="category"
                            v-model="form.category"
                            placeholder="Invoices, Contracts…"
                        />
                        <InputError :message="form.errors.category" />
                    </div>

                    <!-- Entity -->
                    <div class="grid gap-1.5">
                        <Label for="entity">Related Entity</Label>
                        <Select v-model="entityIdString">
                            <SelectTrigger id="entity">
                                <SelectValue placeholder="None" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">None</SelectItem>
                                <SelectItem
                                    v-for="e in entities"
                                    :key="e.id"
                                    :value="String(e.id)"
                                >
                                    {{ e.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.entity_id" />
                    </div>

                    <!-- Description -->
                    <div class="grid gap-1.5">
                        <Label for="description">Description</Label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            class="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        />
                        <InputError :message="form.errors.description" />
                    </div>

                    <SheetFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeSheet"
                            >Cancel</Button
                        >
                        <Button
                            type="submit"
                            :disabled="form.processing || !selectedFile"
                        >
                            Upload
                        </Button>
                    </SheetFooter>
                </form>
            </SheetContent>
        </Sheet>

        <!-- Delete Confirmation Dialog -->
        <Dialog
            :open="deleteDialogOpen"
            @update:open="(v) => !v && (deleteDialogOpen = false)"
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete File</DialogTitle>
                    <DialogDescription>
                        Permanently delete
                        <strong>{{ deletingName }}</strong> from the archive?
                        This cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deleteDialogOpen = false"
                        >Cancel</Button
                    >
                    <Button
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
