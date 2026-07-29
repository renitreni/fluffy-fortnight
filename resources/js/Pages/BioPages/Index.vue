<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    bioPages: Object,
});

const confirmingBioPageDeletion = ref(false);
const bioPageToDelete = ref(null);
const deleteForm = useForm({});

const confirmBioPageDeletion = (id) => {
    bioPageToDelete.value = id;
    confirmingBioPageDeletion.value = true;
};

const deleteBioPage = () => {
    deleteForm.delete(route('bio-pages.destroy', bioPageToDelete.value), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const closeModal = () => {
    confirmingBioPageDeletion.value = false;
    bioPageToDelete.value = null;
    deleteForm.reset();
};
</script>

<template>
    <Head title="Link-in-Bio Pages" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Link-in-Bio Pages
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Your Bio Pages</h3>
                        <Link :href="route('bio-pages.create')">
                            <PrimaryButton>Create Bio Page</PrimaryButton>
                        </Link>
                    </div>

                    <div v-if="bioPages.data.length > 0" class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Title</th>
                                    <th scope="col" class="px-6 py-3">Alias</th>
                                    <th scope="col" class="px-6 py-3">Public Link</th>
                                    <th scope="col" class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="page in bioPages.data" :key="page.id" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                        {{ page.title }}
                                    </td>
                                    <td class="px-6 py-4">{{ page.alias }}</td>
                                    <td class="px-6 py-4">
                                        <a :href="route('bio-pages.show', page.alias)" target="_blank" class="text-brand-500 hover:underline">
                                            /b/{{ page.alias }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-right flex justify-end gap-2">
                                        <Link :href="route('bio-pages.edit', page.id)">
                                            <SecondaryButton size="sm">Edit</SecondaryButton>
                                        </Link>
                                        <DangerButton size="sm" @click="confirmBioPageDeletion(page.id)">Delete</DangerButton>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="text-center py-12 text-gray-500 dark:text-gray-400">
                        You have not created any bio pages yet.
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="confirmingBioPageDeletion" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Are you sure you want to delete this bio page?
                </h2>

                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Once your bio page is deleted, all of its resources and data will be permanently deleted. This action cannot be undone.
                </p>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal"> Cancel </SecondaryButton>
                    <DangerButton
                        class="ml-3"
                        :class="{ 'opacity-25': deleteForm.processing }"
                        :disabled="deleteForm.processing"
                        @click="deleteBioPage"
                    >
                        Delete Bio Page
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
