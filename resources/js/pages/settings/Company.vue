<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import CompanySettingController from '@/actions/App/Http/Controllers/Settings/CompanySettingController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { show as companyShow } from '@/routes/settings/config/company';
import type { BreadcrumbItem } from '@/types';

type CompanySetting = {
    name: string | null;
    address: string | null;
    postal_code: string | null;
    locality: string | null;
    tax_number: string | null;
    logo_path: string | null;
};

const props = defineProps<{
    settings: CompanySetting;
    logoUrl: string | null;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Company', href: companyShow() },
];

const page = usePage();
const flash = computed(
    () =>
        page.props.flash as { success?: string | null; error?: string | null },
);

// Text settings form
const textForm = useForm({
    name: props.settings.name ?? '',
    address: props.settings.address ?? '',
    postal_code: props.settings.postal_code ?? '',
    locality: props.settings.locality ?? '',
    tax_number: props.settings.tax_number ?? '',
});

// Logo upload form
const logoForm = useForm({ logo: null as File | null });
const logoFileInput = ref<HTMLInputElement | null>(null);
const logoPreview = ref<string | null>(props.logoUrl);

function onLogoChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;
    logoForm.logo = file;
    logoPreview.value = URL.createObjectURL(file);
}

function submitTextForm() {
    textForm.put(CompanySettingController.update.url(), {
        preserveScroll: true,
    });
}

function submitLogo() {
    if (!logoForm.logo) return;
    logoForm.post(CompanySettingController.updateLogo.url(), {
        preserveScroll: true,
        onSuccess: () => {
            logoForm.reset();
            if (logoFileInput.value) logoFileInput.value.value = '';
        },
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Company" />

        <SettingsLayout>
            <div class="space-y-8">
                <Heading
                    variant="small"
                    title="Company Settings"
                    description="Manage your company information used on invoices and documents."
                />

                <Alert
                    v-if="flash.success"
                    class="border-green-200 bg-green-50 text-green-800"
                >
                    <AlertDescription>{{ flash.success }}</AlertDescription>
                </Alert>
                <Alert v-if="flash.error" variant="destructive">
                    <AlertDescription>{{ flash.error }}</AlertDescription>
                </Alert>

                <!-- Logo Section -->
                <div class="space-y-4">
                    <h3 class="text-sm font-medium">Company Logo</h3>

                    <div v-if="logoPreview" class="mb-4">
                        <img
                            :src="logoPreview"
                            alt="Company logo"
                            class="max-h-24 max-w-xs rounded border object-contain p-2"
                        />
                    </div>
                    <div
                        v-else
                        class="mb-4 flex h-24 w-48 items-center justify-center rounded border border-dashed text-sm text-muted-foreground"
                    >
                        No logo uploaded
                    </div>

                    <form
                        class="flex items-end gap-4"
                        @submit.prevent="submitLogo"
                    >
                        <div class="grid gap-2">
                            <Label for="logo">Upload new logo</Label>
                            <Input
                                id="logo"
                                ref="logoFileInput"
                                type="file"
                                accept="image/jpeg,image/png,image/svg+xml,image/webp"
                                @change="onLogoChange"
                            />
                            <InputError :message="logoForm.errors.logo" />
                        </div>
                        <Button
                            type="submit"
                            :disabled="!logoForm.logo || logoForm.processing"
                        >
                            Upload Logo
                        </Button>
                    </form>
                </div>

                <Separator />

                <!-- Text Settings Form -->
                <form class="space-y-4" @submit.prevent="submitTextForm">
                    <div class="grid gap-2">
                        <Label for="name">Company Name</Label>
                        <Input
                            id="name"
                            v-model="textForm.name"
                            autocomplete="organization"
                            placeholder="e.g. Acme Lda."
                        />
                        <InputError :message="textForm.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="tax_number">Tax Number (NIF)</Label>
                        <Input
                            id="tax_number"
                            v-model="textForm.tax_number"
                            autocomplete="off"
                            placeholder="e.g. 500 123 456"
                        />
                        <InputError :message="textForm.errors.tax_number" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="address">Address</Label>
                        <Input
                            id="address"
                            v-model="textForm.address"
                            autocomplete="street-address"
                            placeholder="e.g. Rua Exemplo, 123"
                        />
                        <InputError :message="textForm.errors.address" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="postal_code">Postal Code</Label>
                            <Input
                                id="postal_code"
                                v-model="textForm.postal_code"
                                autocomplete="postal-code"
                                placeholder="e.g. 1000-001"
                            />
                            <InputError
                                :message="textForm.errors.postal_code"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="locality">Locality</Label>
                            <Input
                                id="locality"
                                v-model="textForm.locality"
                                autocomplete="address-level2"
                                placeholder="e.g. Lisboa"
                            />
                            <InputError :message="textForm.errors.locality" />
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <Button type="submit" :disabled="textForm.processing"
                            >Save Settings</Button
                        >
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
