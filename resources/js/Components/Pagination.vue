<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: {
        type: Array,
        required: true,
    },
});

let decoder;
const decodeHtml = (label) => {
    if (!label) {
        return '';
    }

    if (typeof window === 'undefined') {
        return label;
    }

    if (!decoder) {
        decoder = document.createElement('textarea');
    }

    decoder.innerHTML = label;
    return decoder.value;
};
</script>

<template>
    <div v-if="links.length > 3">
        <div class="flex flex-wrap -mb-1">
            <template v-for="(link, key) in links" :key="key">
                <div
                    v-if="link.url === null"
                    class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded"
                    v-text="decodeHtml(link.label)"
                />
                <Link
                    v-else
                    :class="[
                        'mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-white dark:hover:bg-gray-800 dark:border-gray-700 dark:text-gray-300 focus:border-brand-500 focus:text-brand-500 transition-colors',
                        {
                            'bg-brand-50 border-brand-500 text-brand-600 dark:bg-brand-900/30 dark:border-brand-500 dark:text-brand-400':
                                link.active,
                        },
                    ]"
                    :href="link.url"
                >
                    {{ decodeHtml(link.label) }}
                </Link>
            </template>
        </div>
    </div>
</template>
