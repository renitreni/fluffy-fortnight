<script setup>
/**
 * Dashboard Page
 *
 * Main authenticated landing page. Showcases the core design system components
 * built on Day 4 and will evolve into the full link management dashboard (Day 8).
 */
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/Badge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import IconButton from '@/Components/IconButton.vue';
import LoadingSpinner from '@/Components/LoadingSpinner.vue';
import Modal from '@/Components/Modal.vue';
import { Head } from '@inertiajs/vue3';
import { useToastStore } from '@/Stores/useToastStore';
import { ref } from 'vue';

const toast = useToastStore();
const showModal = ref(false);

defineProps({
    auth: Object,
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Dashboard
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Welcome back, {{ $page.props.auth.user.name }} 👋
                    </p>
                </div>
                <PrimaryButton @click="showModal = true">
                    <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Shorten URL
                </PrimaryButton>
            </div>
        </template>

        <!-- Stats Overview Cards -->
        <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div v-for="stat in stats" :key="stat.label"
                class="glass rounded-2xl border border-gray-200/50 p-6 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg dark:border-gray-700/50"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ stat.label }}</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ stat.value }}</p>
                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ stat.sub }}</p>
                    </div>
                    <div :class="['flex h-12 w-12 items-center justify-center rounded-xl', stat.iconBg]">
                        <span :class="['text-2xl', stat.iconColor]">{{ stat.icon }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Day 4 Component Showcase -->
        <div class="rounded-2xl border border-gray-200/50 bg-white p-8 shadow-sm dark:border-gray-700/50 dark:bg-gray-900">
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">UI Component Library</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Day 4 design system — shared components for the entire application.
                </p>
            </div>

            <!-- Buttons -->
            <div class="mb-8">
                <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Buttons</h3>
                <div class="flex flex-wrap items-center gap-3">
                    <PrimaryButton>Primary</PrimaryButton>
                    <SecondaryButton>Secondary</SecondaryButton>
                    <DangerButton>Danger</DangerButton>
                    <SecondaryButton :disabled="true">Disabled</SecondaryButton>
                </div>
            </div>

            <!-- Badges -->
            <div class="mb-8">
                <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Badges</h3>
                <div class="flex flex-wrap items-center gap-2">
                    <Badge variant="default">Default</Badge>
                    <Badge variant="primary" :dot="true">Active</Badge>
                    <Badge variant="success" :dot="true">Online</Badge>
                    <Badge variant="warning">Pending</Badge>
                    <Badge variant="danger">Expired</Badge>
                    <Badge variant="info">New</Badge>
                    <Badge variant="success" size="lg">Large</Badge>
                    <Badge variant="danger" size="sm">Small</Badge>
                </div>
            </div>

            <!-- Icon Buttons -->
            <div class="mb-8">
                <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Icon Buttons</h3>
                <div class="flex flex-wrap items-center gap-2">
                    <IconButton label="Copy link" variant="ghost">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" />
                        </svg>
                    </IconButton>
                    <IconButton label="Edit link" variant="outline">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                        </svg>
                    </IconButton>
                    <IconButton label="Delete link" variant="solid">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                    </IconButton>
                </div>
            </div>

            <!-- Loading Spinners -->
            <div class="mb-8">
                <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Loading States</h3>
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex items-center gap-2">
                        <LoadingSpinner size="xs" />
                        <span class="text-xs text-gray-500">xs</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <LoadingSpinner size="sm" />
                        <span class="text-xs text-gray-500">sm</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <LoadingSpinner size="md" />
                        <span class="text-xs text-gray-500">md</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <LoadingSpinner size="lg" />
                        <span class="text-xs text-gray-500">lg</span>
                    </div>
                    <div class="flex items-center gap-2 rounded-lg bg-brand-600 px-3 py-2">
                        <LoadingSpinner size="sm" variant="white" />
                        <span class="text-xs text-white">white variant</span>
                    </div>
                </div>
            </div>

            <!-- Toast Notifications -->
            <div>
                <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Toast Notifications</h3>
                <div class="flex flex-wrap items-center gap-3">
                    <SecondaryButton id="toast-success-btn" @click="toast.success('Link shortened successfully! 🎉')">
                        Success Toast
                    </SecondaryButton>
                    <SecondaryButton id="toast-error-btn" @click="toast.error('Failed to shorten URL. Please try again.')">
                        Error Toast
                    </SecondaryButton>
                    <SecondaryButton id="toast-warning-btn" @click="toast.warning('Link is close to its expiry date.')">
                        Warning Toast
                    </SecondaryButton>
                    <SecondaryButton id="toast-info-btn" @click="toast.info('Your QR code is being generated.')">
                        Info Toast
                    </SecondaryButton>
                </div>
            </div>
        </div>

        <!-- Modal Demo -->
        <Modal :show="showModal" @close="showModal = false">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Shorten a URL</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Paste your long URL below to create a short link. Full shortening functionality will be available in Day 6.
                </p>
                <div class="mt-4">
                    <input
                        type="url"
                        placeholder="https://example.com/very/long/url..."
                        class="block w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm transition focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showModal = false">Cancel</SecondaryButton>
                    <PrimaryButton @click="showModal = false; toast.success('Coming in Day 6! 🚀')">
                        Shorten
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>

<script>
// Page-level data defined as standard options for clarity
export default {
    data() {
        return {
            stats: [
                {
                    label: 'Total Links',
                    value: '0',
                    sub: 'Start shortening URLs',
                    icon: '🔗',
                    iconBg: 'bg-brand-50 dark:bg-brand-900/30',
                    iconColor: '',
                },
                {
                    label: 'Total Clicks',
                    value: '0',
                    sub: 'Across all links',
                    icon: '📊',
                    iconBg: 'bg-emerald-50 dark:bg-emerald-900/30',
                    iconColor: '',
                },
                {
                    label: 'Active Links',
                    value: '0',
                    sub: 'Non-expired links',
                    icon: '✅',
                    iconBg: 'bg-amber-50 dark:bg-amber-900/30',
                    iconColor: '',
                },
                {
                    label: 'Custom Aliases',
                    value: '0',
                    sub: 'Branded short links',
                    icon: '✨',
                    iconBg: 'bg-purple-50 dark:bg-purple-900/30',
                    iconColor: '',
                },
            ],
        };
    },
};
</script>
