<script setup lang="ts">
import { Paperclip, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';

const props = withDefaults(
    defineProps<{
        modelValue?: File | null;
        accept?: string;
        maxSizeMb?: number;
        previewUrl?: string | null;
        label?: string;
    }>(),
    {
        modelValue: null,
        accept: '*/*',
        maxSizeMb: 10,
        previewUrl: null,
        label: 'Choose file',
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: File | null): void;
    (e: 'error', message: string): void;
}>();

const inputRef = ref<HTMLInputElement | null>(null);
const localPreview = ref<string | null>(null);

const preview = computed(() => localPreview.value ?? props.previewUrl);

watch(
    () => props.modelValue,
    (file) => {
        if (!file) {
            localPreview.value = null;
            return;
        }
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                localPreview.value = e.target?.result as string;
            };
            reader.readAsDataURL(file);
        } else {
            localPreview.value = null;
        }
    },
);

function handleChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    if (!file) return;
    if (file.size > props.maxSizeMb * 1024 * 1024) {
        emit('error', `File exceeds ${props.maxSizeMb}MB limit`);
        return;
    }
    emit('update:modelValue', file);
}

function clearFile() {
    emit('update:modelValue', null);
    localPreview.value = null;
    if (inputRef.value) inputRef.value.value = '';
}
</script>

<template>
    <div class="flex flex-col gap-2">
        <div v-if="preview" class="relative inline-block">
            <img
                v-if="modelValue?.type?.startsWith('image/') || previewUrl"
                :src="preview"
                alt="Preview"
                class="max-h-32 max-w-48 rounded-md border object-contain"
            />
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="absolute -top-2 -right-2 size-6 rounded-full bg-destructive text-white hover:bg-destructive/80"
                @click="clearFile"
            >
                <X class="size-3" />
            </Button>
        </div>

        <div
            v-if="modelValue && !modelValue.type?.startsWith('image/')"
            class="flex items-center gap-2 text-sm text-muted-foreground"
        >
            <Paperclip class="size-4 shrink-0" />
            <span class="truncate">{{ modelValue.name }}</span>
            <Button type="button" variant="ghost" size="sm" @click="clearFile">
                <X class="size-4" />
            </Button>
        </div>

        <div>
            <input
                ref="inputRef"
                type="file"
                :accept="accept"
                class="hidden"
                @change="handleChange"
            />
            <Button
                type="button"
                variant="outline"
                size="sm"
                @click="inputRef?.click()"
            >
                <Paperclip class="mr-2 size-4" />
                {{ label }}
            </Button>
        </div>
    </div>
</template>
