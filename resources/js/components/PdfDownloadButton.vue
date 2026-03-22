<script setup lang="ts">
import { FileDown, Loader2 } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';

const props = withDefaults(
    defineProps<{
        href: string;
        label?: string;
        variant?: 'default' | 'outline' | 'secondary' | 'ghost' | 'destructive';
    }>(),
    {
        label: 'Download PDF',
        variant: 'outline',
    },
);

const loading = ref(false);

function download() {
    loading.value = true;
    const link = document.createElement('a');
    link.href = props.href;
    link.target = '_blank';
    link.rel = 'noopener noreferrer';
    link.click();
    setTimeout(() => {
        loading.value = false;
    }, 1500);
}
</script>

<template>
    <Button :variant="variant" :disabled="loading" @click="download">
        <Loader2 v-if="loading" class="mr-2 size-4 animate-spin" />
        <FileDown v-else class="mr-2 size-4" />
        {{ label }}
    </Button>
</template>
