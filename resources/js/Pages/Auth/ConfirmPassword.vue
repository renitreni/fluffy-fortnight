<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Confirm Password" />

        <template #nav-action>
            <Link
                :href="route('dashboard')"
                class="text-sm font-medium text-gray-600 hover:text-brand-600 dark:text-gray-300 dark:hover:text-brand-400 transition-colors"
            >
                Back to dashboard
            </Link>
        </template>

        <!-- Card Container -->
        <div class="relative">
            <div class="absolute -top-6 -right-6 w-32 h-32 bg-gradient-to-br from-brand-400/20 to-purple-400/20 rounded-full blur-2xl"></div>
            <div class="absolute -bottom-6 -left-6 w-40 h-40 bg-gradient-to-br from-pink-400/20 to-brand-400/20 rounded-full blur-2xl"></div>

            <div class="relative rounded-2xl bg-white dark:bg-gray-900 shadow-2xl shadow-gray-200/50 dark:shadow-none ring-1 ring-gray-200/60 dark:ring-gray-700/60 overflow-hidden">
                <div class="h-1.5 bg-gradient-to-r from-brand-500 via-purple-500 to-pink-500"></div>

                <div class="p-6 sm:p-8">
                    <!-- Header -->
                    <div class="text-center mb-8">
                        <div class="flex items-center justify-center w-12 h-12 mx-auto rounded-xl bg-brand-100 dark:bg-brand-900/30 text-brand-600 dark:text-brand-400 mb-4">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">Confirm your password</h1>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            This is a secure area. Please confirm your password before continuing.
                        </p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-5">
                        <!-- Password -->
                        <div>
                            <InputLabel for="password" value="Password" />
                            <div class="mt-1.5 relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <TextInput
                                    id="password"
                                    type="password"
                                    class="pl-10 block w-full"
                                    v-model="form.password"
                                    required
                                    autocomplete="current-password"
                                    autofocus
                                    placeholder="••••••••"
                                />
                            </div>
                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>

                        <!-- Submit Button -->
                        <PrimaryButton class="w-full justify-center py-3 text-base" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            <svg
                                v-if="form.processing"
                                class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                ></path>
                            </svg>
                            <span v-else class="flex items-center gap-2">
                                Confirm
                            </span>
                        </PrimaryButton>
                    </form>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
