<script lang="ts">
export type TableColumn<Row> = {
    key: keyof Row & string;
    label: string;
    sortable?: boolean;
    class?: string;
};
</script>

<script setup lang="ts" generic="T extends Record<string, unknown>">
import {
    ChevronDown,
    ChevronUp,
    ChevronsUpDown,
    Search,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type SortDir = 'asc' | 'desc' | null;

const props = withDefaults(
    defineProps<{
        columns: TableColumn<T>[];
        rows: T[];
        searchable?: boolean;
        searchPlaceholder?: string;
        emptyMessage?: string;
        rowKey?: keyof T & string;
    }>(),
    {
        searchable: false,
        searchPlaceholder: 'Search…',
        emptyMessage: 'No records found.',
        rowKey: 'id' as any,
    },
);

defineSlots<{
    actions(props: { row: T }): any;
    [key: string]: any;
}>();

const search = ref('');
const sortKey = ref<string | null>(null);
const sortDir = ref<SortDir>(null);

function toggleSort(key: string) {
    if (sortKey.value !== key) {
        sortKey.value = key;
        sortDir.value = 'asc';
    } else if (sortDir.value === 'asc') {
        sortDir.value = 'desc';
    } else {
        sortKey.value = null;
        sortDir.value = null;
    }
}

const filtered = computed(() => {
    if (!props.searchable || !search.value.trim()) return props.rows;
    const q = search.value.toLowerCase();
    return props.rows.filter((row) =>
        props.columns.some((col) => {
            const val = row[col.key];
            return typeof val === 'string' && val.toLowerCase().includes(q);
        }),
    );
});

const sorted = computed(() => {
    if (!sortKey.value || !sortDir.value) return filtered.value;
    const key = sortKey.value;
    const dir = sortDir.value;
    return [...filtered.value].sort((a, b) => {
        const av = a[key] ?? '';
        const bv = b[key] ?? '';
        const cmp = String(av).localeCompare(String(bv), undefined, {
            numeric: true,
            sensitivity: 'base',
        });
        return dir === 'asc' ? cmp : -cmp;
    });
});
</script>

<template>
    <div class="flex flex-col gap-3">
        <div v-if="searchable" class="relative w-full max-w-sm">
            <Search
                class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
            />
            <Input
                v-model="search"
                :placeholder="searchPlaceholder"
                class="pl-9"
            />
        </div>

        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead
                        v-for="col in columns"
                        :key="col.key"
                        :class="[
                            col.class,
                            col.sortable ? 'cursor-pointer select-none' : '',
                        ]"
                        @click="col.sortable ? toggleSort(col.key) : undefined"
                    >
                        <span class="inline-flex items-center gap-1">
                            {{ col.label }}
                            <template v-if="col.sortable">
                                <ChevronUp
                                    v-if="
                                        sortKey === col.key && sortDir === 'asc'
                                    "
                                    class="size-3"
                                />
                                <ChevronDown
                                    v-else-if="
                                        sortKey === col.key &&
                                        sortDir === 'desc'
                                    "
                                    class="size-3"
                                />
                                <ChevronsUpDown
                                    v-else
                                    class="size-3 opacity-40"
                                />
                            </template>
                        </span>
                    </TableHead>
                    <TableHead
                        v-if="$slots.actions"
                        class="w-[1%] whitespace-nowrap"
                    />
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableEmpty
                    v-if="sorted.length === 0"
                    :colspan="columns.length + ($slots.actions ? 1 : 0)"
                >
                    {{ emptyMessage }}
                </TableEmpty>
                <TableRow
                    v-for="row in sorted"
                    :key="String((row as any)[rowKey])"
                >
                    <TableCell
                        v-for="col in columns"
                        :key="col.key"
                        :class="col.class"
                    >
                        <slot :name="col.key" :row="row">
                            {{ row[col.key] }}
                        </slot>
                    </TableCell>
                    <TableCell v-if="$slots.actions" class="text-right">
                        <slot name="actions" :row="row" />
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>
</template>
