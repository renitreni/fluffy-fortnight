<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import TeamMemberManager from './Partials/TeamMemberManager.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    workspace: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const isOwner = props.workspace.owner_id === page.props.auth.user.id;

const form = useForm({
    name: props.workspace.name,
});

const updateWorkspace = () => {
    form.put(route('workspaces.update', props.workspace.id), {
        preserveScroll: true,
    });
};

const deleteWorkspace = () => {
    if (confirm('Are you sure you want to delete this workspace? This action cannot be undone and will delete all links associated with it.')) {
        useForm({}).delete(route('workspaces.destroy', props.workspace.id));
    }
};
</script>

<template>
    <Head :title="`Workspace: ${workspace.name}`" />

    <AppLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Workspace Settings
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                
                <div v-if="isOwner" class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
                    <section class="max-w-xl">
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Workspace Information</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Update your workspace's profile information.
                            </p>
                        </header>

                        <form @submit.prevent="updateWorkspace" class="mt-6 space-y-6">
                            <div>
                                <InputLabel for="name" value="Workspace Name" />
                                <TextInput
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    autofocus
                                    autocomplete="name"
                                />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <div class="flex items-center gap-4">
                                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>

                                <Transition
                                    enter-active-class="transition ease-in-out"
                                    enter-from-class="opacity-0"
                                    leave-active-class="transition ease-in-out"
                                    leave-to-class="opacity-0"
                                >
                                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600 dark:text-gray-400">Saved.</p>
                                </Transition>
                            </div>
                        </form>
                    </section>
                </div>

                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
                    <TeamMemberManager :workspace="workspace" />
                </div>

                <div v-if="isOwner" class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
                    <section class="max-w-xl space-y-6">
                        <header>
                            <h2 class="text-lg font-medium text-red-600 dark:text-red-400">Danger Zone</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Once you delete a workspace, there is no going back. Please be certain.
                            </p>
                        </header>

                        <DangerButton @click="deleteWorkspace">Delete Workspace</DangerButton>
                    </section>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
