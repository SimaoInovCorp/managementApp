<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const page = usePage();
const company = page.props.company as
    | { name: string; logoUrl: string | null }
    | undefined;
const hasCustomLogo = company?.logoUrl != null;
</script>

<template>
    <!-- Logo icon slot -->
    <div
        class="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md bg-sidebar-primary text-sidebar-primary-foreground"
    >
        <img
            v-if="hasCustomLogo"
            :src="company!.logoUrl!"
            :alt="company!.name"
            class="size-8 object-contain"
        />
        <AppLogoIcon v-else class="size-6 text-white" />
    </div>

    <!-- Brand name -->
    <div class="ml-1 grid flex-1 text-left text-sm leading-tight">
        <span v-if="hasCustomLogo" class="truncate font-semibold">{{
            company!.name
        }}</span>
        <span v-else class="truncate font-black tracking-tight">
            ino<span class="text-red-500">V</span>corp
        </span>
    </div>
</template>
