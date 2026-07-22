<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="block w-full rounded-lg border-gray-300 bg-white shadow-sm transition duration-150 ease-in-out focus:border-brand-500 focus:ring-brand-500 sm:text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:border-brand-500 dark:focus:ring-brand-500"
        v-model="model"
        ref="input"
    />
</template>
