<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    bioPage: Object,
});

// Theme classes mapping
const themeClasses = computed(() => {
    switch (props.bioPage.theme) {
        case 'dark':
            return {
                bg: 'bg-gray-900 text-white',
                card: 'bg-gray-800 border-gray-700',
                text: 'text-gray-100',
                subText: 'text-gray-400',
                button: 'bg-gray-700 hover:bg-gray-600 text-white border-gray-600',
            };
        case 'brand':
            return {
                bg: 'bg-brand-900 text-white',
                card: 'bg-brand-800 border-brand-700',
                text: 'text-white',
                subText: 'text-brand-200',
                button: 'bg-brand-500 hover:bg-brand-400 text-white border-brand-400',
            };
        case 'light':
        default:
            return {
                bg: 'bg-gray-50 text-gray-900',
                card: 'bg-white border-gray-200 shadow-sm',
                text: 'text-gray-900',
                subText: 'text-gray-500',
                button: 'bg-white hover:bg-gray-50 text-gray-800 border-gray-300 shadow-sm',
            };
    }
});
</script>

<template>
    <Head :title="bioPage.title" />

    <div :class="['min-h-screen py-12 px-4 sm:px-6 lg:px-8 flex flex-col items-center transition-colors duration-200', themeClasses.bg]">
        
        <div class="w-full max-w-md space-y-8">
            <!-- Profile Section -->
            <div class="text-center">
                <!-- Placeholder for profile image -->
                <div v-if="bioPage.profile_image_path" class="mx-auto h-24 w-24 rounded-full overflow-hidden mb-4 border-4 border-white/20 shadow-lg">
                    <img :src="bioPage.profile_image_path" alt="Profile" class="h-full w-full object-cover" />
                </div>
                <div v-else class="mx-auto h-24 w-24 rounded-full overflow-hidden mb-4 border-4 border-white/20 shadow-lg bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center text-white text-3xl font-bold">
                    {{ bioPage.title.charAt(0).toUpperCase() }}
                </div>
                
                <h1 :class="['text-2xl font-bold tracking-tight', themeClasses.text]">
                    {{ bioPage.title }}
                </h1>
                
                <p v-if="bioPage.description" :class="['mt-2 text-sm', themeClasses.subText]">
                    {{ bioPage.description }}
                </p>
            </div>

            <!-- Links Section -->
            <div class="space-y-4 mt-8">
                <a
                    v-for="link in bioPage.links"
                    :key="link.id"
                    :href="'/' + link.short_code"
                    target="_blank"
                    rel="noopener noreferrer"
                    :class="[
                        'block w-full py-4 px-6 text-center rounded-xl border transition-all duration-200 transform hover:scale-105',
                        themeClasses.button
                    ]"
                >
                    <span class="font-medium text-lg">{{ link.title || link.original_url }}</span>
                </a>

                <div v-if="bioPage.links.length === 0" class="text-center py-8 opacity-70">
                    No links have been added yet.
                </div>
            </div>

            <!-- Branding -->
            <div class="pt-12 text-center">
                <a href="/" target="_blank" class="inline-block hover:opacity-80 transition-opacity">
                    <span :class="['text-xs font-semibold uppercase tracking-wider', themeClasses.subText]">
                        Powered by Elido
                    </span>
                </a>
            </div>
        </div>
    </div>
</template>
