<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <!-- InovCorp monogram: stylised "V"  -->
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 40 40"
        :class="className"
        v-bind="$attrs"
        fill="none"
    >
        <!-- Red V mark -->
        <path
            d="M4 6 L20 34 L36 6"
            stroke="#e53e3e"
            stroke-width="6"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
        <!-- Inner lighter V accent -->
        <path
            d="M10 6 L20 26 L30 6"
            stroke="currentColor"
            stroke-width="3"
            stroke-linecap="round"
            stroke-linejoin="round"
            opacity="0.6"
        />
    </svg>
</template>
