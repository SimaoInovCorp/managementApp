<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';

type BadgeVariant = 'default' | 'secondary' | 'destructive' | 'outline';

const props = withDefaults(
    defineProps<{
        status: string;
        variantMap?: Record<string, BadgeVariant>;
    }>(),
    {
        variantMap: () => ({}),
    },
);

const defaultVariants: Record<string, BadgeVariant> = {
    active: 'default',
    inactive: 'secondary',
    pending: 'outline',
    draft: 'outline',
    sent: 'default',
    accepted: 'default',
    rejected: 'destructive',
    cancelled: 'destructive',
    completed: 'default',
    paid: 'default',
    unpaid: 'destructive',
    partial: 'outline',
    open: 'outline',
    closed: 'secondary',
    in_progress: 'outline',
};

const variant = computed<BadgeVariant>(
    () =>
        props.variantMap[props.status] ??
        defaultVariants[props.status.toLowerCase()] ??
        'secondary',
);

const label = computed(() =>
    props.status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()),
);
</script>

<template>
    <Badge :variant="variant">{{ label }}</Badge>
</template>
