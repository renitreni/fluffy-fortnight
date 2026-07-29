<script setup>
import { ref } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import CopyButton from '@/Components/CopyButton.vue';

const props = defineProps({
    apiKeys: Array,
});

const page = usePage();

const creatingApiKey = ref(false);
const confirmDeleteApiKey = ref(false);
const keyToDelete = ref(null);

const form = useForm({
    name: '',
});

const createApiKey = () => {
    form.post(route('api-keys.store'), {
        preserveScroll: true,
        onSuccess: () => {
            creatingApiKey.value = false;
            form.reset();
        },
    });
};

const confirmDeletion = (key) => {
    keyToDelete.value = key;
    confirmDeleteApiKey.value = true;
};

const deleteApiKey = () => {
    router.delete(route('api-keys.destroy', keyToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const closeModal = () => {
    creatingApiKey.value = false;
    confirmDeleteApiKey.value = false;
    form.reset();
};
</script>

<template>
    <AppLayout title="API Keys">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                API Keys
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Flash Message (Raw API Key) -->
                <div v-if="page.props.flash?.apiKey" class="p-6 bg-green-50 rounded-lg border border-green-200">
                    <h3 class="text-lg font-medium text-green-900 mb-2">Save your new API Key</h3>
                    <p class="text-sm text-green-700 mb-4">
                        Please copy this API key and store it somewhere safe. For security reasons, <strong>we cannot show it to you again</strong>.
                    </p>
                    <div class="flex items-center space-x-3 bg-white p-3 rounded-md border border-green-300">
                        <code class="text-gray-900 font-mono text-sm break-all flex-1">{{ page.props.flash.apiKey }}</code>
                        <CopyButton :text="page.props.flash.apiKey" />
                    </div>
                </div>

                <!-- API Keys List -->
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <header class="flex justify-between items-center mb-6">
                        <div>
                            <h2 class="text-lg font-medium text-gray-900">Programmatic Access</h2>
                            <p class="mt-1 text-sm text-gray-600">
                                Create API keys to access our RESTful API. Keys are scoped to your active workspace.
                            </p>
                        </div>
                        <PrimaryButton @click="creatingApiKey = true">
                            Create API Key
                        </PrimaryButton>
                    </header>

                    <div v-if="apiKeys.length === 0" class="text-center py-8 text-gray-500 text-sm">
                        You have not created any API keys yet.
                    </div>

                    <div v-else class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Name</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Key Prefix</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Created At</th>
                                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                        <span class="sr-only">Actions</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr v-for="key in apiKeys" :key="key.id">
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                                        {{ key.name }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 font-mono">
                                        {{ key.key_prefix }}...
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ new Date(key.created_at).toLocaleDateString() }}
                                    </td>
                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                        <button @click="confirmDeletion(key)" class="text-red-600 hover:text-red-900">Revoke</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Key Modal -->
        <Modal :show="creatingApiKey" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    Create a new API Key
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    Provide a recognizable name for this key to identify its purpose.
                </p>

                <div class="mt-6">
                    <InputLabel for="name" value="Key Name" />
                    <TextInput
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="e.g., Zapier Integration"
                        @keyup.enter="createApiKey"
                    />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton @click="closeModal">
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="createApiKey"
                    >
                        Create Key
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Revoke Key Modal -->
        <Modal :show="confirmDeleteApiKey" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    Are you sure you want to revoke this API Key?
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    Revoking this key immediately removes its access. Any applications currently using this key will be denied access. This action cannot be undone.
                </p>

                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton @click="closeModal">
                        Cancel
                    </SecondaryButton>
                    <DangerButton @click="deleteApiKey">
                        Revoke Key
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
