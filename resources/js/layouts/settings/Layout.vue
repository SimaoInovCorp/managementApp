<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { index as articlesIndex } from '@/routes/settings/config/articles';
import { index as calendarActionsIndex } from '@/routes/settings/config/calendar-actions';
import { index as calendarTypesIndex } from '@/routes/settings/config/calendar-types';
import { show as companyShow } from '@/routes/settings/config/company';
import { index as contactRolesIndex } from '@/routes/settings/config/contact-roles';
import { index as countriesIndex } from '@/routes/settings/config/countries';
import { index as logsIndex } from '@/routes/settings/config/logs';
import { index as vatRatesIndex } from '@/routes/settings/config/vat-rates';
import { show } from '@/routes/two-factor';
import { edit as editPassword } from '@/routes/user-password';
import type { NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profile',
        href: editProfile(),
    },
    {
        title: 'Password',
        href: editPassword(),
    },
    {
        title: 'Two-factor auth',
        href: show(),
    },
    {
        title: 'Appearance',
        href: editAppearance(),
    },
];

const configNavItems: NavItem[] = [
    { title: 'Countries', href: countriesIndex() },
    { title: 'Contact Roles', href: contactRolesIndex() },
    { title: 'Calendar Types', href: calendarTypesIndex() },
    { title: 'Calendar Actions', href: calendarActionsIndex() },
    { title: 'VAT Rates', href: vatRatesIndex() },
    { title: 'Articles', href: articlesIndex() },
    { title: 'Company', href: companyShow() },
    { title: 'Logs', href: logsIndex() },
];

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div class="px-4 py-6">
        <Heading
            title="Settings"
            description="Manage your profile and account settings"
        />

        <div class="flex flex-col lg:flex-row lg:space-x-12">
            <aside class="w-full max-w-xl lg:w-48">
                <nav
                    class="flex flex-col space-y-1 space-x-0"
                    aria-label="Settings"
                >
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        variant="ghost"
                        :class="[
                            'w-full justify-start',
                            { 'bg-muted': isCurrentOrParentUrl(item.href) },
                        ]"
                        as-child
                    >
                        <Link :href="item.href">
                            <component :is="item.icon" class="h-4 w-4" />
                            {{ item.title }}
                        </Link>
                    </Button>

                    <Separator class="my-2" />

                    <p
                        class="px-2 py-1 text-xs font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        Configuration
                    </p>

                    <Button
                        v-for="item in configNavItems"
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

            <div class="min-w-0 flex-1">
                <section class="space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
