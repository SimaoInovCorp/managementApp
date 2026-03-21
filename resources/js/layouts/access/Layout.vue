<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { index as permissionsIndex } from '@/routes/access/permissions';
import { index as usersIndex } from '@/routes/access/users';
import type { NavItem } from '@/types';

const navItems: NavItem[] = [
    { title: 'Users', href: usersIndex() },
    { title: 'Permission Groups', href: permissionsIndex() },
];

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div class="px-4 py-6">
        <Heading
            title="Access Management"
            description="Manage users and their permission groups."
        />

        <div class="flex flex-col lg:flex-row lg:space-x-12">
            <aside class="w-full max-w-xl lg:w-48">
                <nav
                    class="flex flex-col space-y-1 space-x-0"
                    aria-label="Access Management"
                >
                    <Button
                        v-for="item in navItems"
                        :key="toUrl(item.href)"
                        variant="ghost"
                        :class="[
                            'w-full justify-start',
                            { 'bg-muted': isCurrentOrParentUrl(item.href) },
                        ]"
                        as-child
                    >
                        <Link :href="item.href">{{ item.title }}</Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="flex-1 max-w-6xl">
                <slot />
            </div>
        </div>
    </div>
</template>
