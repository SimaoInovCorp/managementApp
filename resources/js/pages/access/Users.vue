<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import * as UserMgmt from '@/actions/App/Http/Controllers/Access/UserManagementController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AccessLayout from '@/layouts/access/Layout.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as usersIndex } from '@/routes/access/users';
import type { BreadcrumbItem } from '@/types';

type UserRole = { id: number; name: string };
type AppUser = {
    id: number;
    name: string;
    email: string;
    status: 'active' | 'inactive';
    created_at: string;
    roles: UserRole[];
};
type RoleOption = { id: number; name: string };

const props = defineProps<{ users: AppUser[]; roles: RoleOption[] }>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Access Management' },
    { title: 'Users', href: usersIndex() },
];

const page = usePage();
const flash = computed(() => page.props.flash as { success?: string | null; error?: string | null });

const dialogOpen = ref(false);
const deleteDialogOpen = ref(false);
const editingId = ref<number | null>(null);
const deletingId = ref<number | null>(null);
const isDeleting = ref(false);

const form = useForm({
    name: '',
    email: '',
    phone: '',
    role_id: null as number | null,
});

// Bridge: Select needs a string value; form.role_id stores the integer
const roleIdString = computed({
    get: () => form.role_id !== null ? String(form.role_id) : '',
    set: (val: string) => { form.role_id = val ? parseInt(val, 10) : null; },
});

function openCreate() {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function openEdit(user: AppUser) {
    editingId.value = user.id;
    form.name = user.name;
    form.email = user.email;
    form.phone = '';
    form.role_id = user.roles[0]?.id ?? null;
    form.clearErrors();
    dialogOpen.value = true;
}

function closeDialog() {
    dialogOpen.value = false;
    editingId.value = null;
    form.reset();
    form.clearErrors();
}

function submit() {
    if (editingId.value) {
        form.put(UserMgmt.update.url({ user: editingId.value }), {
            onSuccess: closeDialog,
        });
    } else {
        form.post(UserMgmt.store.url(), {
            onSuccess: closeDialog,
        });
    }
}

function toggleStatus(id: number) {
    router.patch(UserMgmt.toggleStatus.url({ user: id }));
}

function confirmDelete(id: number) {
    deletingId.value = id;
    deleteDialogOpen.value = true;
}

function executeDelete() {
    if (!deletingId.value) return;
    isDeleting.value = true;
    router.delete(UserMgmt.destroy.url({ user: deletingId.value }), {
        onSuccess: () => {
            deleteDialogOpen.value = false;
            deletingId.value = null;
            isDeleting.value = false;
        },
        onError: () => { isDeleting.value = false; },
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Users" />

        <AccessLayout>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <Heading
                        variant="small"
                        title="Users"
                        description="Manage application users and their permission groups."
                    />
                    <Button @click="openCreate">Add User</Button>
                </div>

                <Alert v-if="flash.success" class="border-green-200 bg-green-50 text-green-800">
                    <AlertDescription>{{ flash.success }}</AlertDescription>
                </Alert>
                <Alert v-if="flash.error" variant="destructive">
                    <AlertDescription>{{ flash.error }}</AlertDescription>
                </Alert>

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Permission Group</TableHead>
                            <TableHead class="w-28">Status</TableHead>
                            <TableHead class="w-44 text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableEmpty v-if="!props.users.length" :colspan="5">No users found.</TableEmpty>
                        <TableRow v-for="user in props.users" :key="user.id">
                            <TableCell class="font-medium">{{ user.name }}</TableCell>
                            <TableCell>{{ user.email }}</TableCell>
                            <TableCell>
                                <span v-if="user.roles.length">{{ user.roles[0].name }}</span>
                                <span v-else class="text-muted-foreground text-sm">—</span>
                            </TableCell>
                            <TableCell>
                                <Badge :variant="user.status === 'active' ? 'default' : 'secondary'">
                                    {{ user.status }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-right space-x-1">
                                <Button variant="ghost" size="sm" @click="openEdit(user)">Edit</Button>
                                <Button variant="ghost" size="sm" @click="toggleStatus(user.id)">
                                    {{ user.status === 'active' ? 'Disable' : 'Enable' }}
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="text-destructive hover:text-destructive"
                                    @click="confirmDelete(user.id)"
                                >Delete</Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Create / Edit Dialog -->
            <Dialog v-model:open="dialogOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{{ editingId ? 'Edit User' : 'Add User' }}</DialogTitle>
                    </DialogHeader>
                    <form class="space-y-4" @submit.prevent="submit">
                        <div class="grid gap-2">
                            <Label for="user-name">Name</Label>
                            <Input id="user-name" v-model="form.name" autocomplete="off" placeholder="Full name" />
                            <InputError :message="form.errors.name" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="user-email">Email</Label>
                            <Input id="user-email" v-model="form.email" type="email" autocomplete="off" placeholder="user@example.com" />
                            <InputError :message="form.errors.email" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="user-phone">Phone</Label>
                            <Input id="user-phone" v-model="form.phone" type="tel" autocomplete="off" placeholder="+351 9XX XXX XXX" />
                            <InputError :message="form.errors.phone" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="user-role">Permission Group</Label>
                            <Select v-model="roleIdString">
                                <SelectTrigger id="user-role">
                                    <SelectValue placeholder="Select a permission group" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="role in props.roles"
                                        :key="role.id"
                                        :value="String(role.id)"
                                    >
                                        {{ role.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.role_id" />
                        </div>
                    </form>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="closeDialog">Cancel</Button>
                        <Button type="button" :disabled="form.processing" @click="submit">
                            {{ editingId ? 'Save Changes' : 'Add User' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Delete Confirmation Dialog -->
            <Dialog v-model:open="deleteDialogOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete User</DialogTitle>
                    </DialogHeader>
                    <p class="text-sm text-muted-foreground">
                        Are you sure you want to delete this user? This action cannot be undone.
                    </p>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="deleteDialogOpen = false">Cancel</Button>
                        <Button type="button" variant="destructive" :disabled="isDeleting" @click="executeDelete">
                            Delete
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AccessLayout>
    </AppLayout>
</template>
