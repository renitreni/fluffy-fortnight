<script setup>
import { computed, useAttrs } from 'vue';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    alt: {
        type: String,
        default: 'Elido Logo',
    },
    variant: {
        type: String,
        default: 'mark', // 'mark' | 'wordmark' | 'mark-with-text'
        validator: (v) => ['mark', 'wordmark', 'mark-with-text'].includes(v),
    },
});

const resolvedSrc = computed(() => {
    switch (props.variant) {
        case 'wordmark':
            return '/Elido/logo-word.png';
        case 'mark':
        case 'mark-with-text':
        default:
            return '/Elido/E-Logo-Mark.png';
    }
});

const attrs = useAttrs();
</script>

<template>
    <div
        v-if="variant === 'mark-with-text'"
        class="flex items-center gap-2"
        v-bind="attrs"
    >
        <img
            :src="resolvedSrc"
            :alt="alt"
            class="block select-none object-contain h-full w-auto"
            draggable="false"
        >
        <span class="font-bold text-xl tracking-tight bg-gradient-to-r from-brand-600 to-purple-600 bg-clip-text text-transparent">
            Elido
        </span>
    </div>
    <img
        v-else
        :src="resolvedSrc"
        :alt="alt"
        class="block select-none object-contain"
        draggable="false"
        v-bind="attrs"
    >
</template>
