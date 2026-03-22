<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import CountryController from '@/actions/App/Http/Controllers/Settings/CountryController';
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
import { index as countriesIndex } from '@/routes/settings/config/countries';
import type { BreadcrumbItem } from '@/types';

type Country = { id: number; name: string; code: string };

const props = defineProps<{ countries: { data: Country[] } }>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Countries', href: countriesIndex() },
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

const form = useForm({ name: '', code: '' });

function openCreate() {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function openEdit(item: Country) {
    editingId.value = item.id;
    form.name = item.name;
    form.code = item.code;
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
        form.put(CountryController.update.url({ country: editingId.value }), {
            onSuccess: closeDialog,
        });
    } else {
        form.post(CountryController.store.url(), {
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
        CountryController.destroy.url({ country: deletingId.value }),
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
        <Head title="Countries" />

        <SettingsLayout>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <Heading
                        variant="small"
                        title="Countries"
                        description="Manage the list of countries used in entity addresses."
                    />
                    <Button @click="openCreate">Add Country</Button>
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
                            <TableHead class="w-24">Code</TableHead>
                            <TableHead class="w-32 text-right"
                                >Actions</TableHead
                            >
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableEmpty
                            v-if="!props.countries.data.length"
                            :colspan="3"
                            >No countries found.</TableEmpty
                        >
                        <TableRow
                            v-for="country in props.countries.data"
                            :key="country.id"
                        >
                            <TableCell>{{ country.name }}</TableCell>
                            <TableCell>
                                <span class="font-mono uppercase">{{
                                    country.code
                                }}</span>
                            </TableCell>
                            <TableCell class="space-x-1 text-right">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="openEdit(country)"
                                    >Edit</Button
                                >
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="text-destructive hover:text-destructive"
                                    @click="confirmDelete(country.id)"
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
                            editingId ? 'Edit Country' : 'Add Country'
                        }}</DialogTitle>
                    </DialogHeader>
                    <form class="space-y-4" @submit.prevent="submit">
                        <div class="grid gap-2">
                            <Label for="name">Name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                autocomplete="off"
                                placeholder="e.g. Portugal"
                            />
                            <InputError :message="form.errors.name" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="code"
                                >Code (2 letters, ISO 3166-1 alpha-2)</Label
                            >
                            <Input
                                id="code"
                                v-model="form.code"
                                maxlength="2"
                                class="uppercase"
                                autocomplete="off"
                                placeholder="e.g. PT"
                                @input="
                                    form.code = (form.code ?? '').toUpperCase()
                                "
                            />
                            <InputError :message="form.errors.code" />
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
                            {{ editingId ? 'Save Changes' : 'Add Country' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Delete Confirmation Dialog -->
            <Dialog v-model:open="deleteDialogOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Country</DialogTitle>
                    </DialogHeader>
                    <p class="text-sm text-muted-foreground">
                        Are you sure you want to delete this country? This
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
