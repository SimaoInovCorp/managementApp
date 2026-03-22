<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
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
import { index as logsIndex } from '@/routes/settings/config/logs';
import type { BreadcrumbItem } from '@/types';

type UserOption = { id: number; name: string };

type LogEntry = {
    id: number;
    log_name: string | null;
    description: string;
    event: string | null;
    subject_type: string | null;
    subject_id: number | null;
    causer: { id: number; name: string } | null;
    properties: Record<string, unknown>;
    ip: string | null;
    device: string | null;
    created_at: string;
};

type PaginatedLogs = {
    data: LogEntry[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
};

const props = defineProps<{
    logs: PaginatedLogs;
    filters: { date_from?: string; date_to?: string; user_id?: string };
    users: UserOption[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Logs', href: logsIndex() },
];

const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');
const userId = ref(props.filters.user_id ?? '');

function applyFilters() {
    router.get(
        logsIndex(),
        {
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
            user_id: userId.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function clearFilters() {
    dateFrom.value = '';
    dateTo.value = '';
    userId.value = '';
    router.get(logsIndex(), {}, { preserveState: false, replace: true });
}

function eventVariant(
    event: string | null,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (!event) return 'outline';
    if (event === 'created') return 'default';
    if (event === 'deleted') return 'destructive';
    return 'secondary';
}

function truncateDevice(device: string | null): string {
    if (!device) return '—';
    return device.length > 60 ? device.substring(0, 60) + '…' : device;
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Activity Logs" />

        <SettingsLayout>
            <div class="space-y-4">
                <Heading
                    variant="small"
                    title="Activity Logs"
                    description="Read-only audit trail of all application actions."
                />

                <!-- Filters -->
                <div
                    class="flex flex-wrap items-end gap-4 rounded-md border bg-muted/30 p-4"
                >
                    <div class="grid gap-1.5">
                        <Label for="date-from">From</Label>
                        <Input
                            id="date-from"
                            v-model="dateFrom"
                            type="date"
                            class="w-40"
                        />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="date-to">To</Label>
                        <Input
                            id="date-to"
                            v-model="dateTo"
                            type="date"
                            class="w-40"
                        />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="user-filter">User</Label>
                        <Select v-model="userId">
                            <SelectTrigger id="user-filter" class="w-44">
                                <SelectValue placeholder="All users" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">All users</SelectItem>
                                <SelectItem
                                    v-for="u in users"
                                    :key="u.id"
                                    :value="String(u.id)"
                                >
                                    {{ u.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="flex gap-2">
                        <Button size="sm" @click="applyFilters">Apply</Button>
                        <Button
                            size="sm"
                            variant="outline"
                            @click="clearFilters"
                            >Clear</Button
                        >
                    </div>
                    <p class="ml-auto text-sm text-muted-foreground">
                        {{ logs.total }} records
                    </p>
                </div>

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-36">Date / Time</TableHead>
                            <TableHead class="w-32">User</TableHead>
                            <TableHead class="w-28">Module</TableHead>
                            <TableHead class="w-20">Event</TableHead>
                            <TableHead>Description</TableHead>
                            <TableHead class="w-24">Subject</TableHead>
                            <TableHead class="w-28">IP</TableHead>
                            <TableHead class="w-48">Device</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableEmpty v-if="!logs.data.length" :colspan="8"
                            >No log entries found.</TableEmpty
                        >
                        <TableRow v-for="entry in logs.data" :key="entry.id">
                            <TableCell class="font-mono text-xs">{{
                                entry.created_at
                            }}</TableCell>
                            <TableCell class="text-sm">{{
                                entry.causer?.name ?? '—'
                            }}</TableCell>
                            <TableCell>
                                <span class="text-xs text-muted-foreground">{{
                                    entry.log_name ?? 'default'
                                }}</span>
                            </TableCell>
                            <TableCell>
                                <Badge :variant="eventVariant(entry.event)">
                                    {{ entry.event ?? 'action' }}
                                </Badge>
                            </TableCell>
                            <TableCell class="max-w-xs truncate text-sm">{{
                                entry.description
                            }}</TableCell>
                            <TableCell class="text-xs text-muted-foreground">
                                <span v-if="entry.subject_type"
                                    >{{ entry.subject_type }} #{{
                                        entry.subject_id
                                    }}</span
                                >
                                <span v-else>—</span>
                            </TableCell>
                            <TableCell class="font-mono text-xs">{{
                                entry.ip ?? '—'
                            }}</TableCell>
                            <TableCell
                                class="text-xs text-muted-foreground"
                                :title="entry.device ?? ''"
                            >
                                {{ truncateDevice(entry.device) }}
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Pagination -->
                <div
                    v-if="logs.last_page > 1"
                    class="flex flex-wrap justify-center gap-1 pt-2"
                >
                    <template v-for="link in logs.links" :key="link.label">
                        <Button
                            v-if="link.url"
                            size="sm"
                            :variant="link.active ? 'default' : 'outline'"
                            @click="router.get(link.url)"
                            ><span v-html="link.label"
                        /></Button>
                        <Button v-else size="sm" variant="ghost" disabled
                            ><span v-html="link.label"
                        /></Button>
                    </template>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
