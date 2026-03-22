<script setup lang="ts">
import type { EventClickArg, EventInput } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import type { DateClickArg } from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';
import FullCalendar from '@fullcalendar/vue3';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref, watchEffect } from 'vue';
import CalendarEventController from '@/actions/App/Http/Controllers/CalendarEventController';
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
import AppLayout from '@/layouts/AppLayout.vue';
import { index as calendarIndex } from '@/routes/calendar';
import type { BreadcrumbItem } from '@/types';

// ─── Types ────────────────────────────────────────────────────────────────────

type UserOption = { id: number; name: string };
type EntityOption = { id: number; name: string };
type TypeOption = { id: number; name: string };
type ActionOption = { id: number; name: string };

type EventRow = {
    id: number;
    title: string;
    date: string;
    time: string | null;
    duration_minutes: number;
    user_id: number;
    entity_id: number | null;
    type_id: number | null;
    action_id: number | null;
    description: string | null;
    shared_with: number[];
    knowledge: string | null;
    status: 'draft' | 'confirmed' | 'cancelled';
    start: string;
    extendedProps: Record<string, unknown>;
};

const props = defineProps<{
    events: { data: EventRow[] };
    users: UserOption[];
    clients: EntityOption[];
    types: TypeOption[];
    actions: ActionOption[];
    selectedUserId: number | null;
    selectedEntityId: number | null;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Calendar', href: calendarIndex() },
];

const page = usePage();
const flash = computed(
    () =>
        page.props.flash as { success?: string | null; error?: string | null },
);

// ─── FullCalendar config ──────────────────────────────────────────────────────

function mapEvent(ev: EventRow): EventInput {
    return {
        id: String(ev.id),
        title: ev.title,
        start: ev.start,
        backgroundColor: statusColor(ev.status),
        borderColor: statusColor(ev.status),
        extendedProps: ev.extendedProps,
    };
}

const calendarOptions = reactive({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay',
    },
    height: 'auto',
    editable: false,
    selectable: false,
    dateClick: (info: DateClickArg) => openCreate(info.dateStr),
    eventClick: (info: EventClickArg) => {
        const ev = props.events.data.find(
            (e) => String(e.id) === info.event.id,
        );
        if (ev) openEdit(ev);
    },
    events: props.events.data.map(mapEvent),
});

watchEffect(() => {
    calendarOptions.events = props.events.data.map(mapEvent);
});

function statusColor(status: string): string {
    if (status === 'confirmed') return '#047857'; // green
    if (status === 'cancelled') return '#dc2626'; // red
    return '#6b7280'; // grey for draft
}

// ─── Filter ───────────────────────────────────────────────────────────────────

const filterUserId = ref<string>(
    props.selectedUserId ? String(props.selectedUserId) : '',
);
const filterEntityId = ref<string>(
    props.selectedEntityId ? String(props.selectedEntityId) : '',
);

function applyFilter() {
    const params: Record<string, string> = {};
    if (filterUserId.value) params.user_id = filterUserId.value;
    if (filterEntityId.value) params.entity_id = filterEntityId.value;
    router.get(
        CalendarEventController.index.url(params),
        {},
        { preserveState: true },
    );
}

// ─── Sheet state ──────────────────────────────────────────────────────────────

const sheetOpen = ref(false);
const editingId = ref<number | null>(null);

const form = useForm({
    title: '',
    date: '',
    time: '',
    duration_minutes: '60',
    entity_id: null as number | null,
    type_id: null as number | null,
    action_id: null as number | null,
    description: '',
    shared_with: [] as number[],
    knowledge: '',
    status: 'confirmed' as 'draft' | 'confirmed' | 'cancelled',
});

// Shadcn Select string bridges
const entityIdString = computed({
    get: () => (form.entity_id !== null ? String(form.entity_id) : ''),
    set: (v: string) => {
        form.entity_id = v ? parseInt(v, 10) : null;
    },
});
const typeIdString = computed({
    get: () => (form.type_id !== null ? String(form.type_id) : ''),
    set: (v: string) => {
        form.type_id = v ? parseInt(v, 10) : null;
    },
});
const actionIdString = computed({
    get: () => (form.action_id !== null ? String(form.action_id) : ''),
    set: (v: string) => {
        form.action_id = v ? parseInt(v, 10) : null;
    },
});

function openCreate(dateStr?: string) {
    editingId.value = null;
    form.reset();
    form.date = dateStr ?? new Date().toISOString().split('T')[0];
    form.duration_minutes = '60';
    form.status = 'confirmed';
    form.shared_with = [];
    form.clearErrors();
    sheetOpen.value = true;
}

function openEdit(event: EventRow) {
    editingId.value = event.id;
    form.title = event.title;
    form.date = event.date;
    form.time = event.time ?? '';
    form.duration_minutes = String(event.duration_minutes);
    form.entity_id = event.entity_id;
    form.type_id = event.type_id;
    form.action_id = event.action_id;
    form.description = event.description ?? '';
    form.shared_with = event.shared_with ?? [];
    form.knowledge = event.knowledge ?? '';
    form.status = event.status;
    form.clearErrors();
    sheetOpen.value = true;
}

function closeSheet() {
    sheetOpen.value = false;
}

function submit() {
    const url = editingId.value
        ? CalendarEventController.update.url({ event: editingId.value })
        : CalendarEventController.store.url();
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
        CalendarEventController.destroy.url({ event: deletingId.value }),
        {
            onSuccess: () => {
                deleteDialogOpen.value = false;
                deletingId.value = null;
                isDeleting.value = false;
                sheetOpen.value = false;
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
        <Head title="Calendar" />

        <div class="space-y-4 px-4 py-6">
            <div class="flex items-center justify-between">
                <Heading
                    variant="small"
                    title="Calendar"
                    description="View and manage events. Click a date to create, click an event to edit."
                />
                <Button @click="openCreate()">Add Event</Button>
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
            <div class="flex items-end gap-3 pb-2">
                <div class="grid w-48 gap-1">
                    <Label for="filter-user">Filter by User</Label>
                    <Select v-model="filterUserId">
                        <SelectTrigger id="filter-user"
                            ><SelectValue placeholder="All users"
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All users</SelectItem>
                            <SelectItem
                                v-for="u in props.users"
                                :key="u.id"
                                :value="String(u.id)"
                                >{{ u.name }}</SelectItem
                            >
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid w-48 gap-1">
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
                <Button variant="outline" @click="applyFilter">Apply</Button>
            </div>

            <!-- FullCalendar -->
            <div class="rounded-md border bg-background p-3">
                <FullCalendar :options="calendarOptions" />
            </div>
        </div>

        <!-- ── Create / Edit Sheet ─────────────────────────────────────── -->
        <Sheet v-model:open="sheetOpen">
            <SheetContent class="flex flex-col overflow-hidden p-0 sm:max-w-lg">
                <SheetHeader class="flex-shrink-0 border-b px-6 py-4">
                    <SheetTitle
                        >{{ editingId ? 'Edit' : 'New' }} Calendar
                        Event</SheetTitle
                    >
                </SheetHeader>

                <div class="flex-1 space-y-4 overflow-y-auto px-6 py-4">
                    <!-- Title -->
                    <div class="grid gap-2">
                        <Label for="ce-title"
                            >Title
                            <span class="text-destructive">*</span></Label
                        >
                        <Input
                            id="ce-title"
                            v-model="form.title"
                            placeholder="Event title"
                        />
                        <InputError :message="form.errors.title" />
                    </div>

                    <!-- Date & Time -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="ce-date"
                                >Date
                                <span class="text-destructive">*</span></Label
                            >
                            <Input
                                id="ce-date"
                                v-model="form.date"
                                type="date"
                            />
                            <InputError :message="form.errors.date" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="ce-time">Time</Label>
                            <Input
                                id="ce-time"
                                v-model="form.time"
                                type="time"
                            />
                            <InputError :message="form.errors.time" />
                        </div>
                    </div>

                    <!-- Duration -->
                    <div class="grid gap-2">
                        <Label for="ce-duration">Duration (minutes)</Label>
                        <Input
                            id="ce-duration"
                            v-model="form.duration_minutes"
                            type="number"
                            min="5"
                            max="1440"
                            step="5"
                        />
                        <InputError :message="form.errors.duration_minutes" />
                    </div>

                    <!-- Entity (Client) -->
                    <div class="grid gap-2">
                        <Label for="ce-entity">Client (optional)</Label>
                        <Select v-model="entityIdString">
                            <SelectTrigger id="ce-entity"
                                ><SelectValue placeholder="— None —"
                            /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">— None —</SelectItem>
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

                    <!-- Type & Action -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="ce-type">Type</Label>
                            <Select v-model="typeIdString">
                                <SelectTrigger id="ce-type"
                                    ><SelectValue placeholder="— None —"
                                /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">— None —</SelectItem>
                                    <SelectItem
                                        v-for="t in props.types"
                                        :key="t.id"
                                        :value="String(t.id)"
                                        >{{ t.name }}</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.type_id" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="ce-action">Action</Label>
                            <Select v-model="actionIdString">
                                <SelectTrigger id="ce-action"
                                    ><SelectValue placeholder="— None —"
                                /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">— None —</SelectItem>
                                    <SelectItem
                                        v-for="a in props.actions"
                                        :key="a.id"
                                        :value="String(a.id)"
                                        >{{ a.name }}</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.action_id" />
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="grid gap-2">
                        <Label for="ce-status">Status</Label>
                        <Select v-model="form.status">
                            <SelectTrigger id="ce-status"
                                ><SelectValue
                            /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="confirmed"
                                    >Confirmed</SelectItem
                                >
                                <SelectItem value="draft">Draft</SelectItem>
                                <SelectItem value="cancelled"
                                    >Cancelled</SelectItem
                                >
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.status" />
                    </div>

                    <!-- Description -->
                    <div class="grid gap-2">
                        <Label for="ce-description">Description</Label>
                        <textarea
                            id="ce-description"
                            v-model="form.description"
                            rows="3"
                            placeholder="Event details…"
                            class="flex w-full resize-none rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                        />
                        <InputError :message="form.errors.description" />
                    </div>

                    <!-- Knowledge / Notes -->
                    <div class="grid gap-2">
                        <Label for="ce-knowledge">Knowledge Notes</Label>
                        <textarea
                            id="ce-knowledge"
                            v-model="form.knowledge"
                            rows="3"
                            placeholder="Internal knowledge or follow-up notes…"
                            class="flex w-full resize-none rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                        />
                        <InputError :message="form.errors.knowledge" />
                    </div>
                </div>

                <SheetFooter
                    class="flex flex-shrink-0 justify-between border-t px-6 py-4"
                >
                    <div>
                        <Button
                            v-if="editingId"
                            variant="ghost"
                            class="text-destructive hover:text-destructive"
                            @click="confirmDelete(editingId)"
                            >Delete</Button
                        >
                    </div>
                    <div class="flex gap-2">
                        <Button variant="outline" @click="closeSheet"
                            >Cancel</Button
                        >
                        <Button :disabled="form.processing" @click="submit">
                            {{ editingId ? 'Update' : 'Create' }}
                        </Button>
                    </div>
                </SheetFooter>
            </SheetContent>
        </Sheet>

        <!-- ── Delete Confirmation Dialog ─────────────────────────────── -->
        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete Event</DialogTitle>
                </DialogHeader>
                <p class="text-sm text-muted-foreground">
                    Are you sure you want to delete this calendar event? This
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
