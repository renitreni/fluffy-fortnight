<script setup>
/**
 * Dashboard Page
 *
 * Main authenticated landing page showing link management and statistics.
 */
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/Badge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import IconButton from '@/Components/IconButton.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link as InertiaLink, router, useForm } from '@inertiajs/vue3';
import { useToastStore } from '@/Stores/useToastStore';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';
import LineChart from '@/Components/LineChart.vue';

const props = defineProps({
    auth: Object,
    stats: Array,
    links: Array,
    pagination: Object,
    filters: Object,
    chartData: Object,
});

const toast = useToastStore();

const search = ref(props.filters.search || '');
const range = ref(props.filters.range || 30);

watch(
    [search, range],
    debounce(([searchValue, rangeValue]) => {
        router.get(route('dashboard'), { search: searchValue, range: rangeValue }, { preserveState: true, preserveScroll: true });
    }, 300),
);

// Edit link modal state
const showEditModal = ref(false);
const editingLink = ref(null);
const editForm = useForm({
    title: '',
    is_active: true,
});

const openEditModal = (link) => {
    editingLink.value = link;
    editForm.title = link.title || '';
    editForm.is_active = link.is_active;
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    setTimeout(() => {
        editingLink.value = null;
        editForm.reset();
    }, 200);
};

const submitEdit = () => {
    editForm.put(route('links.update', editingLink.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeEditModal();
        },
    });
};

// Delete link modal state
const showDeleteModal = ref(false);
const deletingLink = ref(null);
const deleteForm = useForm({});

const openDeleteModal = (link) => {
    deletingLink.value = link;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    setTimeout(() => {
        deletingLink.value = null;
    }, 200);
};

const submitDelete = () => {
    deleteForm.delete(route('links.destroy', deletingLink.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeDeleteModal();
        },
    });
};

const copyToClipboard = async (url) => {
    try {
        await navigator.clipboard.writeText(url);
        toast.success('Link copied to clipboard!');
    } catch (err) {
        toast.error('Failed to copy link.');
    }
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Welcome back, {{ $page.props.auth.user.name }} 👋
                    </p>
                </div>
                <InertiaLink :href="route('links.index')">
                    <PrimaryButton>
                        <svg
                            class="mr-2 h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Shorten URL
                    </PrimaryButton>
                </InertiaLink>
            </div>
        </template>

        <!-- Stats Overview Cards -->
        <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div
                v-for="stat in stats"
                :key="stat.label"
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

        <!-- Analytics Chart -->
        <div class="mb-8 rounded-2xl border border-gray-200/50 bg-white shadow-sm dark:border-gray-700/50 dark:bg-gray-900 overflow-hidden">
            <div class="border-b border-gray-200 p-6 dark:border-gray-700 sm:flex sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Click Volume</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Total clicks across all your links over time.
                    </p>
                </div>
                <div class="mt-4 sm:mt-0">
                    <select
                        v-model="range"
                        class="block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white sm:text-sm"
                    >
                        <option :value="7">Last 7 Days</option>
                        <option :value="30">Last 30 Days</option>
                        <option :value="90">Last 90 Days</option>
                    </select>
                </div>
            </div>
            <div class="p-6">
                <LineChart v-if="chartData" :chart-data="chartData" :height="350" />
            </div>
        </div>

        <!-- Links Management -->
        <div
            class="rounded-2xl border border-gray-200/50 bg-white shadow-sm dark:border-gray-700/50 dark:bg-gray-900 overflow-hidden"
        >
            <div class="border-b border-gray-200 p-6 dark:border-gray-700 sm:flex sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Your Links</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Manage your shortened URLs, view clicks, and update settings.
                    </p>
                </div>
                <div class="mt-4 sm:mt-0">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search links..."
                        class="block w-full max-w-xs rounded-lg border-gray-300 bg-white text-sm shadow-sm transition focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="links.length === 0" class="p-12 text-center">
                <svg
                    class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"
                    />
                </svg>
                <h3 class="mt-4 text-sm font-semibold text-gray-900 dark:text-white">No links found</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{
                        filters.search ? 'Try adjusting your search term.' : 'Get started by creating a new short link.'
                    }}
                </p>
                <div class="mt-6" v-if="!filters.search">
                    <InertiaLink :href="route('links.index')">
                        <PrimaryButton>Shorten URL</PrimaryButton>
                    </InertiaLink>
                </div>
            </div>

            <!-- Links Table -->
            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800/50">
                        <tr>
                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"
                            >
                                Link Details
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"
                            >
                                Clicks
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"
                            >
                                Status
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                        <tr
                            v-for="link in links"
                            :key="link.id"
                            class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/50"
                        >
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex flex-col">
                                    <div class="flex items-center gap-2">
                                        <a
                                            :href="link.short_url"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="font-medium text-brand-600 hover:text-brand-500 dark:text-brand-400 dark:hover:text-brand-300"
                                        >
                                            {{ link.short_url }}
                                        </a>
                                        <button
                                            @click="copyToClipboard(link.short_url)"
                                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                    <span
                                        class="mt-1 max-w-[200px] sm:max-w-xs md:max-w-md lg:max-w-lg truncate text-sm text-gray-500 dark:text-gray-400"
                                        :title="link.title || link.original_url"
                                    >
                                        {{ link.title || link.original_url }}
                                    </span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="text-sm text-gray-900 dark:text-white">{{ link.click_count }}</div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <Badge v-if="link.is_active" variant="success" :dot="true">Active</Badge>
                                <Badge v-else variant="default">Archived</Badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <div class="flex justify-end gap-2">
                                    <InertiaLink :href="route('links.analytics', link.id)">
                                        <IconButton label="View Analytics" variant="ghost">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                            </svg>
                                        </IconButton>
                                    </InertiaLink>
                                    <IconButton label="Edit link" variant="ghost" @click="openEditModal(link)">
                                        <svg
                                            class="h-4 w-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke-width="1.5"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"
                                            />
                                        </svg>
                                    </IconButton>
                                    <IconButton
                                        label="Delete link"
                                        variant="ghost"
                                        class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                        @click="openDeleteModal(link)"
                                    >
                                        <svg
                                            class="h-4 w-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke-width="1.5"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"
                                            />
                                        </svg>
                                    </IconButton>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700" v-if="pagination.last_page > 1">
                    <Pagination :links="pagination.links" />
                </div>
            </div>
        </div>

        <!-- Edit Link Modal -->
        <Modal :show="showEditModal" @close="closeEditModal">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Edit Link</h3>
                <form @submit.prevent="submitEdit" class="mt-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                        <input
                            v-model="editForm.title"
                            type="text"
                            class="mt-1 block w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm transition focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            placeholder="Optional title"
                        />
                        <div v-if="editForm.errors.title" class="mt-1 text-xs text-red-500">
                            {{ editForm.errors.title }}
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input
                            v-model="editForm.is_active"
                            id="is_active"
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800"
                        />
                        <label for="is_active" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                            Active (uncheck to archive this link)
                        </label>
                        <div v-if="editForm.errors.is_active" class="mt-1 text-xs text-red-500">
                            {{ editForm.errors.is_active }}
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <SecondaryButton type="button" @click="closeEditModal">Cancel</SecondaryButton>
                        <PrimaryButton type="submit" :disabled="editForm.processing"> Save Changes </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Delete Link Modal -->
        <Modal :show="showDeleteModal" @close="closeDeleteModal">
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30"
                    >
                        <svg
                            class="h-6 w-6 text-red-600 dark:text-red-500"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Delete Link</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Are you sure you want to delete this link? It will no longer redirect users, and this action
                            cannot be undone.
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeDeleteModal">Cancel</SecondaryButton>
                    <DangerButton @click="submitDelete" :disabled="deleteForm.processing"> Delete Link </DangerButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
