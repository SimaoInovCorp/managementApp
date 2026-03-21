<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Archive,
    Building2,
    CalendarDays,
    ClipboardList,
    CreditCard,
    DollarSign,
    FileText,
    Globe,
    Landmark,
    LayoutGrid,
    List,
    Package,
    ReceiptText,
    ScrollText,
    Settings,
    Shield,
    ShoppingCart,
    Tag,
    Truck,
    UserRound,
    Users,
    Wrench,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavCollapsible from '@/components/NavCollapsible.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as permissionsIndex } from '@/routes/access/permissions';
import { index as usersIndex } from '@/routes/access/users';
import { index as archiveIndex } from '@/routes/archive';
import { index as calendarIndex } from '@/routes/calendar';
import { index as contactsIndex } from '@/routes/contacts';
import { index as customerOrdersIndex } from '@/routes/customer_orders';
import {
    clients as clientsRoute,
    suppliers as suppliersRoute,
} from '@/routes/entities';
import { index as bankAccountsIndex } from '@/routes/financial/bank_accounts';
import { index as customerAccountsIndex } from '@/routes/financial/customer_accounts';
import { index as supplierInvoicesIndex } from '@/routes/financial/supplier_invoices';
import { index as proposalsIndex } from '@/routes/proposals';
import { index as articlesIndex } from '@/routes/settings/config/articles';
import { index as calendarActionsIndex } from '@/routes/settings/config/calendar-actions';
import { index as calendarTypesIndex } from '@/routes/settings/config/calendar-types';
import { show as companyShow } from '@/routes/settings/config/company';
import { index as contactRolesIndex } from '@/routes/settings/config/contact-roles';
import { index as countriesIndex } from '@/routes/settings/config/countries';
import { index as logsIndex } from '@/routes/settings/config/logs';
import { index as vatRatesIndex } from '@/routes/settings/config/vat-rates';
import { index as supplierOrdersIndex } from '@/routes/supplier_orders';
import { index as workOrdersIndex } from '@/routes/work_orders';
import type { NavGroupItem, NavItem } from '@/types';

const page = usePage();
const permissions = computed(() => (page.props.permissions as string[]) ?? []);
const can = (perm: string) => permissions.value.includes(perm);

const mainNavItems = computed<NavItem[]>(() => [
    { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
]);

const operationsNavItems = computed<NavItem[]>(() => [
    ...(can('proposals.read')
        ? [{ title: 'Proposals', href: proposalsIndex(), icon: FileText }]
        : []),
    ...(can('customer_orders.read')
        ? [
              {
                  title: 'Customer Orders',
                  href: customerOrdersIndex(),
                  icon: ShoppingCart,
              },
          ]
        : []),
    ...(can('supplier_orders.read')
        ? [
              {
                  title: 'Supplier Orders',
                  href: supplierOrdersIndex(),
                  icon: Truck,
              },
          ]
        : []),
    ...(can('work_orders.read')
        ? [{ title: 'Work Orders', href: workOrdersIndex(), icon: Wrench }]
        : []),
]);

const entitiesNavItems = computed<NavItem[]>(() => [
    ...(can('clients.read')
        ? [{ title: 'Clients', href: clientsRoute(), icon: Building2 }]
        : []),
    ...(can('suppliers.read')
        ? [{ title: 'Suppliers', href: suppliersRoute(), icon: Package }]
        : []),
    ...(can('contacts.read')
        ? [{ title: 'Contacts', href: contactsIndex(), icon: UserRound }]
        : []),
]);

const financialGroup = computed<NavGroupItem>(() => ({
    title: 'Financial',
    icon: DollarSign,
    children: [
        ...(can('financial_bank.read')
            ? [
                  {
                      title: 'Bank Accounts',
                      href: bankAccountsIndex(),
                      icon: Landmark,
                  },
              ]
            : []),
        ...(can('financial_current_account.read')
            ? [
                  {
                      title: 'Customer Accounts',
                      href: customerAccountsIndex(),
                      icon: CreditCard,
                  },
              ]
            : []),
        ...(can('financial_invoices.read')
            ? [
                  {
                      title: 'Supplier Invoices',
                      href: supplierInvoicesIndex(),
                      icon: ReceiptText,
                  },
              ]
            : []),
    ],
}));

const calendarNavItems = computed<NavItem[]>(() => [
    ...(can('calendar.read')
        ? [{ title: 'Calendar', href: calendarIndex(), icon: CalendarDays }]
        : []),
]);

const archiveNavItems = computed<NavItem[]>(() => [
    ...(can('digital_archive.read')
        ? [{ title: 'Archive', href: archiveIndex(), icon: Archive }]
        : []),
]);

const accessNavItems = computed<NavItem[]>(() => [
    ...(can('access_users.read')
        ? [{ title: 'Users', href: usersIndex(), icon: Users }]
        : []),
    ...(can('access_permissions.read')
        ? [
              {
                  title: 'Permission Groups',
                  href: permissionsIndex(),
                  icon: Shield,
              },
          ]
        : []),
]);

const settingsGroup = computed<NavGroupItem>(() => ({
    title: 'Settings',
    icon: Settings,
    children: [
        ...(can('settings_countries.read')
            ? [{ title: 'Countries', href: countriesIndex(), icon: Globe }]
            : []),
        ...(can('settings_roles.read')
            ? [
                  {
                      title: 'Contact Roles',
                      href: contactRolesIndex(),
                      icon: UserRound,
                  },
              ]
            : []),
        ...(can('settings_calendar_types.read')
            ? [
                  {
                      title: 'Calendar Types',
                      href: calendarTypesIndex(),
                      icon: CalendarDays,
                  },
              ]
            : []),
        ...(can('settings_calendar_actions.read')
            ? [
                  {
                      title: 'Calendar Actions',
                      href: calendarActionsIndex(),
                      icon: ClipboardList,
                  },
              ]
            : []),
        ...(can('settings_articles.read')
            ? [{ title: 'Articles', href: articlesIndex(), icon: Tag }]
            : []),
        ...(can('settings_vat.read')
            ? [{ title: 'VAT Rates', href: vatRatesIndex(), icon: ScrollText }]
            : []),
        ...(can('settings_company.read')
            ? [{ title: 'Company', href: companyShow(), icon: Building2 }]
            : []),
        ...(can('settings_logs.read')
            ? [{ title: 'Activity Logs', href: logsIndex(), icon: List }]
            : []),
    ],
}));
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" label="Main" />
            <NavMain
                v-if="operationsNavItems.length"
                :items="operationsNavItems"
                label="Operations"
            />
            <NavMain
                v-if="entitiesNavItems.length"
                :items="entitiesNavItems"
                label="Business"
            />
            <NavCollapsible
                v-if="financialGroup.children.length"
                :group="financialGroup"
            />
            <NavMain
                v-if="calendarNavItems.length"
                :items="calendarNavItems"
                label="Planning"
            />
            <NavMain
                v-if="archiveNavItems.length"
                :items="archiveNavItems"
                label="Documents"
            />
            <NavMain
                v-if="accessNavItems.length"
                :items="accessNavItems"
                label="Access Management"
            />
            <NavCollapsible
                v-if="settingsGroup.children.length"
                :group="settingsGroup"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
