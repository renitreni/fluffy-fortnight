<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm, usePage } from '@inertiajs/vue3';

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
    ip_anonymization: user.ip_anonymization,
    data_retention_days: user.data_retention_days,
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Privacy & Data Retention</h2>

            <p class="mt-1 text-sm text-gray-600">
                Update your account's privacy settings and data retention policies.
            </p>
        </header>

        <form @submit.prevent="form.patch(route('profile.update'))" class="mt-6 space-y-6">
            <div class="flex items-center gap-3">
                <input
                    type="checkbox"
                    id="ip_anonymization"
                    class="rounded border-gray-300 text-brand-600 shadow-sm focus:ring-brand-500"
                    v-model="form.ip_anonymization"
                />
                <InputLabel for="ip_anonymization" value="Anonymize IP Addresses" />
            </div>
            <p class="text-xs text-gray-500">
                If enabled, we will not store the IP address or exact location of users who click your links.
            </p>

            <div>
                <InputLabel for="data_retention_days" value="Data Retention (Days)" />
                <TextInput
                    id="data_retention_days"
                    type="number"
                    class="mt-1 block w-full"
                    v-model="form.data_retention_days"
                    min="1"
                />
                <InputError class="mt-2" :message="form.errors.data_retention_days" />
                <p class="mt-2 text-xs text-gray-500">
                    Leave blank to retain data indefinitely. Otherwise, click data older than this many days will be permanently deleted.
                </p>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">Saved.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
