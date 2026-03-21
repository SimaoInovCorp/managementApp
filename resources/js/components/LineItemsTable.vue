<script setup lang="ts">
import { Plus, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';
import ArticleSearch from '@/components/ArticleSearch.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type Article = {
    id: number;
    name: string;
    reference?: string | null;
    unit_price?: number | null;
};

type VatRate = {
    id: number;
    name: string;
    rate: number;
};

export type LineItem = {
    article_id: number | null;
    article?: Article | null;
    description: string;
    quantity: number;
    unit_price: number;
    vat_rate_id: number | null;
    discount_percent?: number;
};

const props = withDefaults(
    defineProps<{
        modelValue: LineItem[];
        articles?: Article[];
        vatRates?: VatRate[];
        showVat?: boolean;
        showDiscount?: boolean;
        currency?: string;
    }>(),
    {
        articles: () => [],
        vatRates: () => [],
        showVat: true,
        showDiscount: false,
        currency: '€',
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', lines: LineItem[]): void;
}>();

function fmt(value: number) {
    return value.toFixed(2);
}

function lineTotal(line: LineItem): number {
    const base = line.quantity * line.unit_price;
    const discounted = props.showDiscount
        ? base * (1 - (line.discount_percent ?? 0) / 100)
        : base;
    const vatRate = props.vatRates.find((v) => v.id === line.vat_rate_id);
    return vatRate ? discounted * (1 + vatRate.rate / 100) : discounted;
}

const subtotal = computed(() =>
    props.modelValue.reduce((s, l) => s + l.quantity * l.unit_price, 0),
);

const total = computed(() =>
    props.modelValue.reduce((s, l) => s + lineTotal(l), 0),
);

function updateLine(index: number, patch: Partial<LineItem>) {
    const updated = props.modelValue.map((l, i) =>
        i === index ? { ...l, ...patch } : l,
    );
    emit('update:modelValue', updated);
}

function fillFromArticle(index: number, article: Article | null) {
    if (!article) return;
    updateLine(index, {
        article_id: article.id,
        article,
        description: article.name,
        unit_price: article.unit_price ?? 0,
    });
}

function addLine() {
    emit('update:modelValue', [
        ...props.modelValue,
        {
            article_id: null,
            description: '',
            quantity: 1,
            unit_price: 0,
            vat_rate_id: props.vatRates[0]?.id ?? null,
            discount_percent: 0,
        },
    ]);
}

function removeLine(index: number) {
    emit(
        'update:modelValue',
        props.modelValue.filter((_, i) => i !== index),
    );
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>Article</TableHead>
                    <TableHead>Description</TableHead>
                    <TableHead class="w-20 text-right">Qty</TableHead>
                    <TableHead class="w-28 text-right">Unit Price</TableHead>
                    <TableHead v-if="showDiscount" class="w-20 text-right"
                        >Disc %</TableHead
                    >
                    <TableHead v-if="showVat && vatRates.length" class="w-28"
                        >VAT</TableHead
                    >
                    <TableHead class="w-28 text-right">Total</TableHead>
                    <TableHead class="w-10" />
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-for="(line, index) in modelValue" :key="index">
                    <TableCell>
                        <ArticleSearch
                            v-if="articles.length"
                            :articles="articles"
                            :model-value="line.article ?? null"
                            @update:model-value="fillFromArticle(index, $event)"
                        />
                        <span v-else class="text-sm text-muted-foreground"
                            >—</span
                        >
                    </TableCell>
                    <TableCell>
                        <Input
                            :model-value="line.description"
                            placeholder="Description"
                            @update:model-value="
                                updateLine(index, {
                                    description: String($event),
                                })
                            "
                        />
                    </TableCell>
                    <TableCell>
                        <Input
                            type="number"
                            min="0"
                            step="1"
                            :model-value="line.quantity"
                            class="text-right"
                            @update:model-value="
                                updateLine(index, { quantity: Number($event) })
                            "
                        />
                    </TableCell>
                    <TableCell>
                        <Input
                            type="number"
                            min="0"
                            step="0.01"
                            :model-value="line.unit_price"
                            class="text-right"
                            @update:model-value="
                                updateLine(index, {
                                    unit_price: Number($event),
                                })
                            "
                        />
                    </TableCell>
                    <TableCell v-if="showDiscount">
                        <Input
                            type="number"
                            min="0"
                            max="100"
                            step="0.01"
                            :model-value="line.discount_percent ?? 0"
                            class="text-right"
                            @update:model-value="
                                updateLine(index, {
                                    discount_percent: Number($event),
                                })
                            "
                        />
                    </TableCell>
                    <TableCell v-if="showVat && vatRates.length">
                        <Select
                            :model-value="
                                line.vat_rate_id !== null
                                    ? String(line.vat_rate_id)
                                    : undefined
                            "
                            @update:model-value="
                                updateLine(index, {
                                    vat_rate_id: $event ? Number($event) : null,
                                })
                            "
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="VAT" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="vat in vatRates"
                                    :key="vat.id"
                                    :value="String(vat.id)"
                                >
                                    {{ vat.name }} ({{ vat.rate }}%)
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </TableCell>
                    <TableCell class="text-right text-sm font-medium">
                        {{ currency }}{{ fmt(lineTotal(line)) }}
                    </TableCell>
                    <TableCell>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            @click="removeLine(index)"
                        >
                            <Trash2 class="size-4 text-destructive" />
                        </Button>
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>

        <div class="flex items-start justify-between gap-4">
            <Button type="button" variant="outline" size="sm" @click="addLine">
                <Plus class="mr-2 size-4" />
                Add Line
            </Button>

            <div class="flex flex-col items-end gap-1 text-sm">
                <div class="text-muted-foreground">
                    Subtotal:
                    <span class="font-medium text-foreground"
                        >{{ currency }}{{ fmt(subtotal) }}</span
                    >
                </div>
                <div class="text-base font-semibold">
                    Total: {{ currency }}{{ fmt(total) }}
                </div>
            </div>
        </div>
    </div>
</template>
