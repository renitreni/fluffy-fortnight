<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import CreateWorkspaceForm from './Partials/CreateWorkspaceForm.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    workspaces: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <Head title="Workspaces" />

    <AppLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Workspaces
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Your Workspaces</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Workspaces you own or are a member of.
                            </p>
                        </header>

                        <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            <Link
                                v-for="workspace in workspaces"
                                :key="workspace.id"
                                :href="route('workspaces.show', workspace.id)"
                                class="block p-6 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow hover:bg-gray-50 dark:hover:bg-gray-600 transition"
                            >
                                <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ workspace.name }}</h5>
                                <p class="font-normal text-gray-700 dark:text-gray-400">
                                    Role: {{ workspace.pivot?.role || 'Owner' }}
                                </p>
                            </Link>
                        </div>
                    </section>
                </div>

                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
                    <CreateWorkspaceForm />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
