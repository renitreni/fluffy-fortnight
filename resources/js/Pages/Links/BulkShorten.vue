<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ToastNotification from '@/Components/ToastNotification.vue';

const props = defineProps({
    bulkJobs: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    csv_file: null,
});

const fileInput = ref(null);

const submit = () => {
    form.post(route('bulk.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('csv_file');
            if (fileInput.value) fileInput.value.value = '';
        },
    });
};

const formatStatus = (status) => {
    switch (status) {
        case 'completed':
            return 'Completed';
        case 'processing':
            return 'Processing';
        case 'failed':
            return 'Failed';
        default:
            return 'Pending';
    }
};

const statusClass = (status) => {
    switch (status) {
        case 'completed':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
        case 'processing':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400';
        case 'failed':
            return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300';
    }
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString();
};

const refreshJobs = () => {
    router.reload({ only: ['bulkJobs'] });
};
</script>

<template>
    <AppLayout title="Bulk Shorten">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Bulk URL Shortening</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <ToastNotification />

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Upload CSV</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Upload a CSV file containing the URLs you want to shorten. The CSV must have a header row with a
                        <code>url</code> column. Optional columns: <code>title</code>, <code>custom_alias</code>. Max
                        file size: 5MB.
                    </p>

                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <input
                                ref="fileInput"
                                type="file"
                                accept=".csv,.txt"
                                @input="form.csv_file = $event.target.files[0]"
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 dark:text-gray-400 dark:file:bg-gray-700 dark:file:text-gray-300"
                                :disabled="form.processing"
                            />
                            <InputError :message="form.errors.csv_file" class="mt-2" />
                        </div>

                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="form.processing || !form.csv_file">
                                Upload & Shorten
                            </PrimaryButton>

                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p v-if="form.recentlySuccessful" class="text-sm text-green-600 dark:text-green-400">
                                    Upload started.
                                </p>
                            </Transition>
                        </div>
                    </form>
                </div>

                <!-- Recent Jobs -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Recent Bulk Jobs</h3>
                        <button
                            @click="refreshJobs"
                            class="text-sm text-brand-600 hover:text-brand-800 dark:text-brand-400 dark:hover:text-brand-300"
                        >
                            Refresh list
                        </button>
                    </div>

                    <div v-if="bulkJobs.length === 0" class="text-center py-6 text-gray-500 dark:text-gray-400">
                        No bulk shortening jobs found.
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                    >
                                        File
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                    >
                                        Date
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                    >
                                        Status
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                    >
                                        Progress
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                    >
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="job in bulkJobs" :key="job.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ job.original_filename }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ formatDate(job.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                            :class="statusClass(job.status)"
                                        >
                                            {{ formatStatus(job.status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ job.processed_rows }} / {{ job.total_rows }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a
                                            v-if="job.status === 'completed' && job.result_file_path"
                                            :href="route('bulk.download', job.id)"
                                            class="text-brand-600 hover:text-brand-900 dark:text-brand-400 dark:hover:text-brand-300"
                                        >
                                            Download Results
                                        </a>
                                        <span v-else class="text-gray-400 dark:text-gray-600 cursor-not-allowed">
                                            Download Results
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
