<script setup lang="ts">
import { Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Input } from '@/components/ui/input';

type Article = {
    id: number;
    name: string;
    reference?: string | null;
    unit_price?: number | null;
    [key: string]: unknown;
};

const props = defineProps<{
    articles: Article[];
    modelValue: Article | null;
    placeholder?: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: Article | null): void;
}>();

const query = ref(props.modelValue?.name ?? '');
const open = ref(false);

const suggestions = computed(() => {
    if (!query.value.trim()) return [];
    const q = query.value.toLowerCase();
    return props.articles
        .filter(
            (a) =>
                a.name.toLowerCase().includes(q) ||
                (a.reference ?? '').toLowerCase().includes(q),
        )
        .slice(0, 10);
});

function select(article: Article) {
    query.value = article.name;
    open.value = false;
    emit('update:modelValue', article);
}

function handleInput() {
    open.value = true;
    if (!query.value.trim()) {
        emit('update:modelValue', null);
    }
}

function handleBlur() {
    setTimeout(() => {
        open.value = false;
    }, 150);
}
</script>

<template>
    <div class="relative">
        <div class="relative">
            <Search
                class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
            />
            <Input
                v-model="query"
                :placeholder="placeholder ?? 'Search articles…'"
                class="pl-9"
                autocomplete="off"
                @input="handleInput"
                @blur="handleBlur"
                @focus="open = true"
            />
        </div>
        <ul
            v-if="open && suggestions.length"
            class="absolute z-50 mt-1 w-full rounded-md border bg-popover shadow-md"
        >
            <li
                v-for="article in suggestions"
                :key="article.id"
                class="cursor-pointer px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground"
                @mousedown.prevent="select(article)"
            >
                <span class="font-medium">{{ article.name }}</span>
                <span
                    v-if="article.reference"
                    class="ml-2 text-xs text-muted-foreground"
                    >{{ article.reference }}</span
                >
            </li>
        </ul>
    </div>
</template>
