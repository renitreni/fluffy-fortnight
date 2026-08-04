<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    plans: Array,
    intent: Object,
    subscription: Object,
});

const form = useForm({
    plan_id: null,
    interval: 'monthly',
});

const startCheckout = (planId) => {
    form.plan_id = planId;
    form.post(route('billing.checkout'));
};
</script>

<template>
    <Head title="Billing & Subscription" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Billing & Subscription
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Current Subscription Status -->
                <div class="p-4 sm:p-8 glass glass-shadow sm:rounded-lg">
                    <section>
                        <header class="flex justify-between items-center mb-6">
                            <div>
                                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    Current Plan
                                </h2>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    Manage your billing and subscription details.
                                </p>
                            </div>
                            <div v-if="subscription">
                                <a :href="route('billing.portal')" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                                    Manage in Stripe
                                </a>
                            </div>
                        </header>

                        <div v-if="subscription" class="mt-4 bg-brand-50 dark:bg-brand-900/20 text-brand-700 dark:text-brand-300 p-4 rounded-md">
                            You are currently subscribed. Your subscription status is: <strong>{{ subscription.stripe_status }}</strong>
                        </div>
                        <div v-else class="mt-4 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 p-4 rounded-md">
                            You are currently on the Free plan. Upgrade to unlock advanced features.
                        </div>
                    </section>
                </div>

                <!-- Plans -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div v-for="plan in plans" :key="plan.id" class="glass p-6 rounded-2xl flex flex-col justify-between">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ plan.display_name }}</h3>
                            <div class="flex items-baseline mb-4">
                                <span class="text-4xl font-extrabold text-gray-900 dark:text-white">${{ plan.price_monthly }}</span>
                                <span class="text-gray-500 dark:text-gray-400 ml-1">/mo</span>
                            </div>
                            <ul class="space-y-3 mb-6">
                                <template v-for="(enabled, feature) in plan.features" :key="feature">
                                    <li v-if="plan.features" class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                        <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        {{ feature }}
                                    </li>
                                </template>
                                <li class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                    <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    Max Links: {{ plan.max_links }}
                                </li>
                            </ul>
                        </div>
                        <PrimaryButton v-if="plan.price_monthly > 0" @click="startCheckout(plan.id)" class="w-full justify-center" :disabled="form.processing">
                            Subscribe
                        </PrimaryButton>
                        <SecondaryButton v-else class="w-full justify-center" disabled>
                            Current Plan
                        </SecondaryButton>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
