<script setup lang="ts">
import { computed } from 'vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type EntityOption = {
    id: number;
    name: string;
    type?: 'client' | 'supplier';
};

const props = withDefaults(
    defineProps<{
        modelValue: number | string | null;
        entities: EntityOption[];
        filterType?: 'client' | 'supplier' | 'both';
        placeholder?: string;
        disabled?: boolean;
    }>(),
    {
        filterType: 'both',
        placeholder: 'Select entity',
        disabled: false,
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: number | string | null): void;
}>();

const filteredEntities = computed(() => {
    if (props.filterType === 'both') return props.entities;
    return props.entities.filter((e) => !e.type || e.type === props.filterType);
});
</script>

<template>
    <Select
        :model-value="modelValue !== null ? String(modelValue) : undefined"
        :disabled="disabled"
        @update:model-value="
            emit('update:modelValue', $event ? Number($event) : null)
        "
    >
        <SelectTrigger>
            <SelectValue :placeholder="placeholder" />
        </SelectTrigger>
        <SelectContent>
            <SelectItem
                v-for="entity in filteredEntities"
                :key="entity.id"
                :value="String(entity.id)"
            >
                {{ entity.name }}
            </SelectItem>
        </SelectContent>
    </Select>
</template>
