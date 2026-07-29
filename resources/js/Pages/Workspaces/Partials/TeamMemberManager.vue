<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    workspace: Object,
});

const page = usePage();
const isOwner = props.workspace.owner_id === page.props.auth.user.id;

const inviteForm = useForm({
    email: '',
    role: 'viewer',
});

const inviteMember = () => {
    inviteForm.post(route('workspaces.invitations.store', props.workspace.id), {
        preserveScroll: true,
        onSuccess: () => inviteForm.reset(),
    });
};

const cancelInvitation = (invitationId) => {
    useForm({}).delete(route('workspaces.invitations.destroy', { workspace: props.workspace.id, invitation: invitationId }), {
        preserveScroll: true,
    });
};

const removeMember = (userId) => {
    if (confirm('Are you sure you want to remove this member?')) {
        useForm({}).delete(route('workspaces.members.destroy', { workspace: props.workspace.id, user: userId }), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Team Members</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Manage members and invitations for this workspace.
            </p>
        </header>

        <!-- Current Members -->
        <div class="mt-6 space-y-4">
            <h3 class="text-md font-medium text-gray-900 dark:text-gray-100">Current Members</h3>
            <div class="space-y-3">
                <!-- Owner -->
                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ workspace.owner.name }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ workspace.owner.email }}</div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full dark:bg-green-900 dark:text-green-300">Owner</span>
                    </div>
                </div>

                <!-- Other Members -->
                <div v-for="member in workspace.members" :key="member.id" class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ member.name }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ member.email }}</div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="px-2 py-1 text-xs font-semibold text-blue-800 bg-blue-100 rounded-full dark:bg-blue-900 dark:text-blue-300">{{ member.pivot.role }}</span>
                        <DangerButton v-if="isOwner" @click="removeMember(member.id)" class="px-2 py-1 text-xs">Remove</DangerButton>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Invitations -->
        <div v-if="workspace.invitations && workspace.invitations.length > 0" class="mt-8 space-y-4">
            <h3 class="text-md font-medium text-gray-900 dark:text-gray-100">Pending Invitations</h3>
            <div class="space-y-3">
                <div v-for="invitation in workspace.invitations" :key="invitation.id" class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-dashed border-gray-300 dark:border-gray-600">
                    <div class="flex items-center space-x-3">
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ invitation.email }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Invited as {{ invitation.role }}</div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <DangerButton v-if="isOwner" @click="cancelInvitation(invitation.id)" class="px-2 py-1 text-xs">Cancel</DangerButton>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invite Form -->
        <form v-if="isOwner" @submit.prevent="inviteMember" class="mt-8 space-y-6 max-w-xl border-t border-gray-200 dark:border-gray-700 pt-6">
            <h3 class="text-md font-medium text-gray-900 dark:text-gray-100">Invite New Member</h3>
            <div>
                <InputLabel for="email" value="Email Address" />
                <TextInput
                    id="email"
                    v-model="inviteForm.email"
                    type="email"
                    class="mt-1 block w-full"
                    required
                />
                <InputError class="mt-2" :message="inviteForm.errors.email" />
            </div>

            <div>
                <InputLabel for="role" value="Role" />
                <select
                    id="role"
                    v-model="inviteForm.role"
                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 dark:focus:border-brand-600 focus:ring-brand-500 dark:focus:ring-brand-600 rounded-md shadow-sm"
                    required
                >
                    <option value="admin">Admin</option>
                    <option value="editor">Editor</option>
                    <option value="viewer">Viewer</option>
                </select>
                <InputError class="mt-2" :message="inviteForm.errors.role" />
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="inviteForm.processing">Send Invitation</PrimaryButton>
                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="inviteForm.recentlySuccessful" class="text-sm text-gray-600 dark:text-gray-400">Invitation sent.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
