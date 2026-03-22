<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import CalendarActionController from '@/actions/App/Http/Controllers/Settings/CalendarActionController';
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
import { index as actionsIndex } from '@/routes/settings/config/calendar-actions';
import type { BreadcrumbItem } from '@/types';

type CalendarAction = { id: number; name: string };

const props = defineProps<{ actions: { data: CalendarAction[] } }>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Calendar Actions', href: actionsIndex() },
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

function openEdit(item: CalendarAction) {
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
            CalendarActionController.update.url({
                calendarAction: editingId.value,
            }),
            {
                onSuccess: closeDialog,
            },
        );
    } else {
        form.post(CalendarActionController.store.url(), {
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
        CalendarActionController.destroy.url({
            calendarAction: deletingId.value,
        }),
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
        <Head title="Calendar Actions" />

        <SettingsLayout>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <Heading
                        variant="small"
                        title="Calendar Actions"
                        description="Define the actions that can be taken on calendar events (e.g. Confirmed, Cancelled)."
                    />
                    <Button @click="openCreate">Add Action</Button>
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
                        <TableEmpty
                            v-if="!props.actions.data.length"
                            :colspan="2"
                            >No calendar actions found.</TableEmpty
                        >
                        <TableRow
                            v-for="action in props.actions.data"
                            :key="action.id"
                        >
                            <TableCell>{{ action.name }}</TableCell>
                            <TableCell class="space-x-1 text-right">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="openEdit(action)"
                                    >Edit</Button
                                >
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="text-destructive hover:text-destructive"
                                    @click="confirmDelete(action.id)"
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
                            editingId ? 'Edit Action' : 'Add Action'
                        }}</DialogTitle>
                    </DialogHeader>
                    <form class="space-y-4" @submit.prevent="submit">
                        <div class="grid gap-2">
                            <Label for="name">Name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                autocomplete="off"
                                placeholder="e.g. Confirmed"
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
                            {{ editingId ? 'Save Changes' : 'Add Action' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Delete Confirmation Dialog -->
            <Dialog v-model:open="deleteDialogOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Calendar Action</DialogTitle>
                    </DialogHeader>
                    <p class="text-sm text-muted-foreground">
                        Are you sure you want to delete this calendar action?
                        This action cannot be undone.
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
