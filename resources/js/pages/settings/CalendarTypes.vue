<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import CalendarTypeController from '@/actions/App/Http/Controllers/Settings/CalendarTypeController';
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
import { index as typesIndex } from '@/routes/settings/config/calendar-types';
import type { BreadcrumbItem } from '@/types';

type CalendarType = { id: number; name: string };

const props = defineProps<{ types: { data: CalendarType[] } }>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Calendar Types', href: typesIndex() },
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

const form = useForm({ name: '' });

function openCreate() {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function openEdit(item: CalendarType) {
    editingId.value = item.id;
    form.name = item.name;
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
        form.put(
            CalendarTypeController.update.url({
                calendarType: editingId.value,
            }),
            {
                onSuccess: closeDialog,
            },
        );
    } else {
        form.post(CalendarTypeController.store.url(), {
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
        CalendarTypeController.destroy.url({ calendarType: deletingId.value }),
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
        <Head title="Calendar Types" />

        <SettingsLayout>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <Heading
                        variant="small"
                        title="Calendar Types"
                        description="Define the types of calendar events (e.g. Meeting, Holiday, Task)."
                    />
                    <Button @click="openCreate">Add Type</Button>
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
                            <TableHead class="w-32 text-right"
                                >Actions</TableHead
                            >
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableEmpty v-if="!props.types.data.length" :colspan="2"
                            >No calendar types found.</TableEmpty
                        >
                        <TableRow
                            v-for="type in props.types.data"
                            :key="type.id"
                        >
                            <TableCell>{{ type.name }}</TableCell>
                            <TableCell class="space-x-1 text-right">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="openEdit(type)"
                                    >Edit</Button
                                >
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="text-destructive hover:text-destructive"
                                    @click="confirmDelete(type.id)"
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
                            editingId ? 'Edit Type' : 'Add Type'
                        }}</DialogTitle>
                    </DialogHeader>
                    <form class="space-y-4" @submit.prevent="submit">
                        <div class="grid gap-2">
                            <Label for="name">Name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                autocomplete="off"
                                placeholder="e.g. Meeting"
                            />
                            <InputError :message="form.errors.name" />
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
                            {{ editingId ? 'Save Changes' : 'Add Type' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Delete Confirmation Dialog -->
            <Dialog v-model:open="deleteDialogOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Calendar Type</DialogTitle>
                    </DialogHeader>
                    <p class="text-sm text-muted-foreground">
                        Are you sure you want to delete this calendar type? This
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
