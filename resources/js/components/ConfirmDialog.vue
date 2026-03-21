<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

withDefaults(
    defineProps<{
        open: boolean;
        title?: string;
        description?: string;
        confirmLabel?: string;
        cancelLabel?: string;
        variant?: 'default' | 'destructive';
    }>(),
    {
        title: 'Are you sure?',
        description: 'This action cannot be undone.',
        confirmLabel: 'Confirm',
        cancelLabel: 'Cancel',
        variant: 'default',
    },
);

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'confirm'): void;
    (e: 'cancel'): void;
}>();

function handleConfirm() {
    emit('confirm');
    emit('update:open', false);
}

function handleCancel() {
    emit('cancel');
    emit('update:open', false);
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="handleCancel">{{
                    cancelLabel
                }}</Button>
                <Button :variant="variant" @click="handleConfirm">{{
                    confirmLabel
                }}</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
