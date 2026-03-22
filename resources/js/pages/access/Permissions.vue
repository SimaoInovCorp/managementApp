<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import * as PermGroupCtrl from '@/actions/App/Http/Controllers/Access/PermissionGroupController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Sheet,
    SheetContent,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AccessLayout from '@/layouts/access/Layout.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as permissionsIndex } from '@/routes/access/permissions';
import type { BreadcrumbItem } from '@/types';

type RolePermission = { id: number; name: string };
type Role = {
    id: number;
    name: string;
    users_count: number;
    permissions: RolePermission[];
};

const props = defineProps<{
    roles: Role[];
    allMenus: string[];
    menuLabels: Record<string, string>;
    allActions: string[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Access Management' },
    { title: 'Permission Groups', href: permissionsIndex() },
];

const page = usePage();
const flash = computed(
    () =>
        page.props.flash as { success?: string | null; error?: string | null },
);

const sheetOpen = ref(false);
const deleteDialogOpen = ref(false);
const editingId = ref<number | null>(null);
const deletingId = ref<number | null>(null);
const isDeleting = ref(false);
const isSubmitting = ref(false);

const roleName = ref('');
const roleNameError = ref('');
const selectedPermissions = ref<string[]>([]);

function openCreate() {
    editingId.value = null;
    roleName.value = '';
    roleNameError.value = '';
    selectedPermissions.value = [];
    sheetOpen.value = true;
}

function openEdit(role: Role) {
    editingId.value = role.id;
    roleName.value = role.name;
    roleNameError.value = '';
    selectedPermissions.value = role.permissions.map((p) => p.name);
    sheetOpen.value = true;
}

function closeSheet() {
    sheetOpen.value = false;
}

function togglePermission(menu: string, action: string) {
    const perm = `${menu}.${action}`;
    const idx = selectedPermissions.value.indexOf(perm);
    if (idx >= 0) {
        selectedPermissions.value.splice(idx, 1);
    } else {
        selectedPermissions.value.push(perm);
    }
}

function hasPermission(menu: string, action: string): boolean {
    return selectedPermissions.value.includes(`${menu}.${action}`);
}

function getMenuCheckedState(menu: string): boolean | 'indeterminate' {
    const count = props.allActions.filter((a) =>
        selectedPermissions.value.includes(`${menu}.${a}`),
    ).length;
    if (count === 0) return false;
    if (count === props.allActions.length) return true;
    return 'indeterminate';
}

function toggleMenuAll(menu: string) {
    const all = props.allActions.map((a) => `${menu}.${a}`);
    const allSelected = all.every((p) => selectedPermissions.value.includes(p));
    if (allSelected) {
        selectedPermissions.value = selectedPermissions.value.filter(
            (p) => !all.includes(p),
        );
    } else {
        all.forEach((p) => {
            if (!selectedPermissions.value.includes(p))
                selectedPermissions.value.push(p);
        });
    }
}

function submit() {
    roleNameError.value = '';
    if (!roleName.value.trim()) {
        roleNameError.value = 'The name field is required.';
        return;
    }
    isSubmitting.value = true;

    const data = {
        name: roleName.value.trim(),
        permissions: selectedPermissions.value,
    };

    if (editingId.value) {
        router.put(PermGroupCtrl.update.url({ role: editingId.value }), data, {
            onSuccess: closeSheet,
            onError: (errors) => {
                roleNameError.value = errors.name ?? '';
                isSubmitting.value = false;
            },
        });
    } else {
        router.post(PermGroupCtrl.store.url(), data, {
            onSuccess: closeSheet,
            onError: (errors) => {
                roleNameError.value = errors.name ?? '';
                isSubmitting.value = false;
            },
        });
    }
}

function confirmDelete(id: number) {
    deletingId.value = id;
    deleteDialogOpen.value = true;
}

function executeDelete() {
    if (!deletingId.value) return;
    isDeleting.value = true;
    router.delete(PermGroupCtrl.destroy.url({ role: deletingId.value }), {
        onSuccess: () => {
            deleteDialogOpen.value = false;
            deletingId.value = null;
            isDeleting.value = false;
        },
        onError: () => {
            isDeleting.value = false;
        },
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Permission Groups" />

        <AccessLayout>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <Heading
                        variant="small"
                        title="Permission Groups"
                        description="Define roles and the permissions each role carries."
                    />
                    <Button @click="openCreate">Add Group</Button>
                </div>

                <Alert
                    v-if="flash.success"
                    class="border-green-200 bg-green-50 text-green-800"
                >
                    <AlertDescription>{{ flash.success }}</AlertDescription>
                </Alert>
                <Alert v-if="flash.error" variant="destructive">
                    <AlertDescription>{{ flash.error }}</AlertDescription>
                </Alert>

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead class="w-32">Users</TableHead>
                            <TableHead class="w-44">Permissions</TableHead>
                            <TableHead class="w-32 text-right"
                                >Actions</TableHead
                            >
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableEmpty v-if="!props.roles.length" :colspan="4"
                            >No permission groups found.</TableEmpty
                        >
                        <TableRow v-for="role in props.roles" :key="role.id">
                            <TableCell class="font-medium">{{
                                role.name
                            }}</TableCell>
                            <TableCell>{{ role.users_count }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">
                                {{ role.permissions.length }} /
                                {{ allMenus.length * allActions.length }}
                            </TableCell>
                            <TableCell class="space-x-1 text-right">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="openEdit(role)"
                                    >Edit</Button
                                >
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="text-destructive hover:text-destructive"
                                    @click="confirmDelete(role.id)"
                                    >Delete</Button
                                >
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Create / Edit Sheet (slide-over) — large to fit the permission matrix -->
            <Sheet v-model:open="sheetOpen">
                <SheetContent
                    class="flex flex-col overflow-hidden p-0 sm:max-w-[700px]"
                >
                    <SheetHeader class="flex-shrink-0 border-b px-6 py-4">
                        <SheetTitle>{{
                            editingId
                                ? 'Edit Permission Group'
                                : 'Add Permission Group'
                        }}</SheetTitle>
                    </SheetHeader>

                    <div
                        v-if="sheetOpen"
                        class="flex-1 space-y-6 overflow-y-auto px-6 py-4"
                    >
                        <!-- Group name -->
                        <div class="grid gap-2">
                            <Label for="role-name">Group Name</Label>
                            <Input
                                id="role-name"
                                v-model="roleName"
                                autocomplete="off"
                                placeholder="e.g. Manager"
                            />
                            <InputError :message="roleNameError" />
                        </div>

                        <!-- Permission Matrix -->
                        <div>
                            <p class="mb-3 text-sm font-medium">Permissions</p>
                            <div class="overflow-hidden rounded-md border">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b bg-muted/50">
                                            <th
                                                class="px-3 py-2 text-left font-medium"
                                            >
                                                Section
                                            </th>
                                            <th
                                                class="w-12 px-3 py-2 text-center text-xs font-medium text-muted-foreground uppercase"
                                            >
                                                All
                                            </th>
                                            <th
                                                v-for="action in allActions"
                                                :key="action"
                                                class="w-20 px-3 py-2 text-center font-medium capitalize"
                                            >
                                                {{ action }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="menu in allMenus"
                                            :key="menu"
                                            class="border-b transition-colors last:border-0 hover:bg-muted/30"
                                        >
                                            <td class="px-3 py-2 font-medium">
                                                {{ menuLabels[menu] ?? menu }}
                                            </td>
                                            <!-- Row "select all" toggle -->
                                            <td class="px-3 py-2 text-center">
                                                <Checkbox
                                                    :checked="
                                                        getMenuCheckedState(
                                                            menu,
                                                        )
                                                    "
                                                    @update:checked="
                                                        () =>
                                                            toggleMenuAll(menu)
                                                    "
                                                />
                                            </td>
                                            <!-- Per-action checkboxes -->
                                            <td
                                                v-for="action in allActions"
                                                :key="action"
                                                class="px-3 py-2 text-center"
                                            >
                                                <Checkbox
                                                    :checked="
                                                        hasPermission(
                                                            menu,
                                                            action,
                                                        )
                                                    "
                                                    @update:checked="
                                                        () =>
                                                            togglePermission(
                                                                menu,
                                                                action,
                                                            )
                                                    "
                                                />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <SheetFooter
                        class="flex flex-shrink-0 justify-end gap-2 border-t px-6 py-4"
                    >
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeSheet"
                            >Cancel</Button
                        >
                        <Button
                            type="button"
                            :disabled="isSubmitting"
                            @click="submit"
                        >
                            {{ editingId ? 'Save Changes' : 'Add Group' }}
                        </Button>
                    </SheetFooter>
                </SheetContent>
            </Sheet>

            <!-- Delete Confirmation Dialog -->
            <Dialog v-model:open="deleteDialogOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Permission Group</DialogTitle>
                    </DialogHeader>
                    <p class="text-sm text-muted-foreground">
                        Are you sure you want to delete this permission group?
                        Roles with assigned users cannot be deleted.
                    </p>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="deleteDialogOpen = false"
                            >Cancel</Button
                        >
                        <Button
                            type="button"
                            variant="destructive"
                            :disabled="isDeleting"
                            @click="executeDelete"
                        >
                            Delete
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AccessLayout>
    </AppLayout>
</template>
