<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ArticleController from '@/actions/App/Http/Controllers/Settings/ArticleController';
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
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { index as articlesIndex } from '@/routes/settings/config/articles';
import type { BreadcrumbItem } from '@/types';

// ─── Types ────────────────────────────────────────────────────────────────────

type VatRateOption = { id: number; name: string; rate: string };

type ArticleRow = {
    id: number;
    reference: string;
    name: string;
    description: string | null;
    price: number;
    vat_id: number;
    vat: { id: number; name: string; rate: string } | null;
    photo_path: string | null;
    photo_url: string | null;
    notes: string | null;
    status: 'active' | 'inactive';
    created_at: string;
};

// ─── Props ────────────────────────────────────────────────────────────────────

const props = defineProps<{
    articles: { data: ArticleRow[] };
    vatRates: VatRateOption[];
}>();

// ─── Breadcrumbs ──────────────────────────────────────────────────────────────

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Articles', href: articlesIndex() },
];

// ─── Flash ────────────────────────────────────────────────────────────────────

const page = usePage();
const flash = computed(
    () =>
        page.props.flash as { success?: string | null; error?: string | null },
);

// ─── Sheet ────────────────────────────────────────────────────────────────────

const sheetOpen = ref(false);
const editingId = ref<number | null>(null);
const previewUrl = ref<string | null>(null);
const photoInputRef = ref<HTMLInputElement | null>(null);

const form = useForm({
    _method: 'POST' as 'POST' | 'PUT',
    reference: '',
    name: '',
    description: '',
    price: '',
    vat_id: null as number | null,
    photo: null as File | null,
    notes: '',
    status: 'active' as 'active' | 'inactive',
});

// Shadcn Select needs strings; convert to/from integer IDs
const vatIdString = computed({
    get: () => (form.vat_id !== null ? String(form.vat_id) : ''),
    set: (v: string) => {
        form.vat_id = v ? parseInt(v, 10) : null;
    },
});

// ─── Photo handling ───────────────────────────────────────────────────────────

function onPhotoChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0] ?? null;
    form.photo = file;
    if (previewUrl.value?.startsWith('blob:')) {
        URL.revokeObjectURL(previewUrl.value);
    }
    previewUrl.value = file ? URL.createObjectURL(file) : null;
}

function clearPhoto() {
    form.photo = null;
    if (previewUrl.value?.startsWith('blob:')) {
        URL.revokeObjectURL(previewUrl.value);
    }
    previewUrl.value = null;
    if (photoInputRef.value) photoInputRef.value.value = '';
}

// ─── Form open / close ────────────────────────────────────────────────────────

function openCreate() {
    editingId.value = null;
    previewUrl.value = null;
    form.reset();
    form._method = 'POST';
    form.clearErrors();
    sheetOpen.value = true;
}

function openEdit(article: ArticleRow) {
    editingId.value = article.id;
    form._method = 'PUT';
    form.reference = article.reference;
    form.name = article.name;
    form.description = article.description ?? '';
    form.price = String(article.price);
    form.vat_id = article.vat_id;
    form.photo = null;
    form.notes = article.notes ?? '';
    form.status = article.status;
    previewUrl.value = article.photo_url;
    form.clearErrors();
    if (photoInputRef.value) photoInputRef.value.value = '';
    sheetOpen.value = true;
}

function closeSheet() {
    sheetOpen.value = false;
}

function submit() {
    const url = editingId.value
        ? ArticleController.update.url({ article: editingId.value })
        : ArticleController.store.url();

    form.post(url, {
        forceFormData: true,
        onSuccess: closeSheet,
    });
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
        ArticleController.destroy.url({ article: deletingId.value }),
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

// ─── Formatting ───────────────────────────────────────────────────────────────

function formatPrice(value: number): string {
    return new Intl.NumberFormat('pt-PT', {
        style: 'currency',
        currency: 'EUR',
    }).format(value);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Articles" />

        <SettingsLayout>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <Heading
                        variant="small"
                        title="Articles"
                        description="Manage your product and service catalogue."
                    />
                    <Button @click="openCreate">Add Article</Button>
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
                            <TableHead class="w-12"></TableHead>
                            <TableHead class="w-28">Ref.</TableHead>
                            <TableHead>Name</TableHead>
                            <TableHead class="w-32 text-right">Price</TableHead>
                            <TableHead class="w-36">VAT</TableHead>
                            <TableHead class="w-24">Status</TableHead>
                            <TableHead class="w-28 text-right"
                                >Actions</TableHead
                            >
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableEmpty
                            v-if="!props.articles.data.length"
                            :colspan="7"
                        >
                            No articles found.
                        </TableEmpty>
                        <TableRow
                            v-for="article in props.articles.data"
                            :key="article.id"
                        >
                            <!-- Photo thumbnail -->
                            <TableCell>
                                <img
                                    v-if="article.photo_url"
                                    :src="article.photo_url"
                                    :alt="article.name"
                                    class="h-8 w-8 rounded object-cover"
                                />
                                <div
                                    v-else
                                    class="flex h-8 w-8 items-center justify-center rounded bg-muted text-xs text-muted-foreground"
                                >
                                    —
                                </div>
                            </TableCell>
                            <TableCell
                                class="font-mono text-sm text-muted-foreground"
                            >
                                {{ article.reference }}
                            </TableCell>
                            <TableCell class="font-medium">{{
                                article.name
                            }}</TableCell>
                            <TableCell class="text-right tabular-nums">
                                {{ formatPrice(article.price) }}
                            </TableCell>
                            <TableCell class="text-sm">
                                <span v-if="article.vat"
                                    >{{ article.vat.name }} ({{
                                        article.vat.rate
                                    }}%)</span
                                >
                                <span v-else class="text-muted-foreground"
                                    >—</span
                                >
                            </TableCell>
                            <TableCell>
                                <Badge
                                    :variant="
                                        article.status === 'active'
                                            ? 'default'
                                            : 'secondary'
                                    "
                                >
                                    {{ article.status }}
                                </Badge>
                            </TableCell>
                            <TableCell class="space-x-1 text-right">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="openEdit(article)"
                                    >Edit</Button
                                >
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="text-destructive hover:text-destructive"
                                    @click="confirmDelete(article.id)"
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
                    class="flex flex-col overflow-hidden p-0 sm:max-w-[540px]"
                >
                    <SheetHeader class="flex-shrink-0 border-b px-6 py-4">
                        <SheetTitle
                            >{{
                                editingId ? 'Edit' : 'Add'
                            }}
                            Article</SheetTitle
                        >
                    </SheetHeader>

                    <div class="flex-1 space-y-4 overflow-y-auto px-6 py-4">
                        <!-- Reference + Name -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label for="art-ref"
                                    >Reference
                                    <span class="text-destructive"
                                        >*</span
                                    ></Label
                                >
                                <Input
                                    id="art-ref"
                                    v-model="form.reference"
                                    autocomplete="off"
                                    placeholder="e.g. SRV-001"
                                />
                                <InputError :message="form.errors.reference" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="art-name"
                                    >Name
                                    <span class="text-destructive"
                                        >*</span
                                    ></Label
                                >
                                <Input
                                    id="art-name"
                                    v-model="form.name"
                                    autocomplete="off"
                                    placeholder="Article name"
                                />
                                <InputError :message="form.errors.name" />
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="grid gap-2">
                            <Label for="art-desc">Description</Label>
                            <textarea
                                id="art-desc"
                                v-model="form.description"
                                rows="3"
                                placeholder="Optional description"
                                class="flex min-h-[80px] w-full resize-none rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            />
                            <InputError :message="form.errors.description" />
                        </div>

                        <!-- Price + VAT -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label for="art-price"
                                    >Price (€)
                                    <span class="text-destructive"
                                        >*</span
                                    ></Label
                                >
                                <Input
                                    id="art-price"
                                    v-model="form.price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00"
                                />
                                <InputError :message="form.errors.price" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="art-vat"
                                    >VAT Rate
                                    <span class="text-destructive"
                                        >*</span
                                    ></Label
                                >
                                <Select v-model="vatIdString">
                                    <SelectTrigger id="art-vat">
                                        <SelectValue placeholder="Select VAT" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="vat in props.vatRates"
                                            :key="vat.id"
                                            :value="String(vat.id)"
                                        >
                                            {{ vat.name }} ({{ vat.rate }}%)
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.vat_id" />
                            </div>
                        </div>

                        <!-- Photo upload -->
                        <div class="grid gap-2">
                            <Label for="art-photo">Photo</Label>
                            <div
                                v-if="previewUrl"
                                class="flex items-center gap-3"
                            >
                                <img
                                    :src="previewUrl"
                                    alt="Photo preview"
                                    class="h-16 w-16 rounded border object-cover"
                                />
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    @click="clearPhoto"
                                >
                                    Remove photo
                                </Button>
                            </div>
                            <input
                                id="art-photo"
                                ref="photoInputRef"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                class="block w-full cursor-pointer text-sm text-muted-foreground file:mr-4 file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-primary-foreground hover:file:bg-primary/90"
                                @change="onPhotoChange"
                            />
                            <p class="text-xs text-muted-foreground">
                                Accepted: JPG, PNG, WebP · Max 5 MB
                            </p>
                            <InputError :message="form.errors.photo" />
                        </div>

                        <!-- Notes -->
                        <div class="grid gap-2">
                            <Label for="art-notes">Notes</Label>
                            <textarea
                                id="art-notes"
                                v-model="form.notes"
                                rows="3"
                                placeholder="Optional internal notes"
                                class="flex min-h-[80px] w-full resize-none rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            />
                            <InputError :message="form.errors.notes" />
                        </div>

                        <!-- Status -->
                        <div class="grid gap-2">
                            <Label for="art-status">Status</Label>
                            <Select v-model="form.status">
                                <SelectTrigger id="art-status" class="w-36">
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
                            <InputError :message="form.errors.status" />
                        </div>
                    </div>

                    <SheetFooter
                        class="flex flex-shrink-0 justify-end gap-2 border-t px-6 py-4"
                    >
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeSheet"
                            >Cancel</Button
                        >
                        <Button
                            type="button"
                            :disabled="form.processing"
                            @click="submit"
                        >
                            {{ editingId ? 'Save Changes' : 'Add Article' }}
                        </Button>
                    </SheetFooter>
                </SheetContent>
            </Sheet>

            <!-- ── Delete Confirmation Dialog ──────────────────────────────── -->
            <Dialog v-model:open="deleteDialogOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Article</DialogTitle>
                    </DialogHeader>
                    <p class="text-sm text-muted-foreground">
                        Are you sure you want to delete this article? This
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
