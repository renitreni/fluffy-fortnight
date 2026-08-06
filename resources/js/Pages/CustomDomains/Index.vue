<script setup>
import { ref, onMounted } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import LoadingSpinner from '@/Components/LoadingSpinner.vue';
import { useToastStore } from '@/Stores/useToastStore';
import CopyButton from '@/Components/CopyButton.vue';

const props = defineProps({
    domains: {
        type: Array,
        required: true,
    },
    dnsTarget: {
        type: String,
        required: true,
    },
});

const toast = useToastStore();
const page = usePage();

const form = useForm({
    domain: '',
});

function submit() {
    form.post(route('custom-domains.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('domain');
        },
        onError: () => {
            toast.error('Could not add domain. Please check errors.');
        }
    });
}

function verifyDomain(domain) {
    router.post(route('custom-domains.verify', domain.id), {}, {
        preserveScroll: true,
    });
}

function deleteDomain(domain) {
    if (confirm(`Are you sure you want to delete ${domain.domain}?`)) {
        router.delete(route('custom-domains.destroy', domain.id), {
            preserveScroll: true,
        });
    }
}

const mounted = ref(false);
onMounted(() => {
    mounted.value = true;
});
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-purple-600 shadow-lg shadow-brand-500/30">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 text-white"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <circle cx="12" cy="12" r="10" />
                        <path d="M2 12h20" />
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">Custom Domains</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Use your own domains to serve branded short links.</p>
                </div>
            </div>
        </template>

        <div
            class="space-y-8"
            :class="{
                'opacity-0 translate-y-4': !mounted,
                'opacity-100 translate-y-0 transition-all duration-500': mounted,
            }"
        >
            <!-- Add Domain Card -->
            <div class="overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-xl shadow-gray-200/50 dark:shadow-none ring-1 ring-gray-200/60 dark:ring-gray-700/60 p-6 sm:p-8">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Add a Custom Domain</h2>
                
                <form @submit.prevent="submit" class="flex flex-col sm:flex-row gap-4 items-start">
                    <div class="flex-1 w-full space-y-2">
                        <input
                            id="domain"
                            v-model="form.domain"
                            type="text"
                            placeholder="e.g. link.yourbrand.com"
                            class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            :disabled="form.processing"
                        />
                        <InputError :message="form.errors.domain" />
                    </div>
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 disabled:opacity-50"
                        :disabled="form.processing || !form.domain.trim()"
                    >
                        <LoadingSpinner v-if="form.processing" size="sm" variant="white" class="mr-2" />
                        Add Domain
                    </button>
                </form>
            </div>

            <!-- Domain List -->
            <div class="space-y-4">
                <div 
                    v-for="domain in domains" 
                    :key="domain.id"
                    class="overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200/60 dark:ring-gray-700/60 p-6"
                >
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div>
                            <div class="flex items-center gap-3">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ domain.domain }}</h3>
                                <span v-if="domain.is_verified" class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-900/20 dark:text-green-400">
                                    Verified
                                </span>
                                <span v-else class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20 dark:bg-yellow-900/20 dark:text-yellow-400">
                                    Pending Verification
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Added on {{ new Date(domain.created_at).toLocaleDateString() }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                v-if="!domain.is_verified"
                                @click="verifyDomain(domain)"
                                class="inline-flex items-center rounded-md bg-white dark:bg-gray-700 px-3 py-1.5 text-sm font-semibold text-brand-600 dark:text-brand-400 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600"
                            >
                                Verify Now
                            </button>
                            <button
                                @click="deleteDomain(domain)"
                                class="inline-flex items-center rounded-md bg-white dark:bg-gray-700 px-3 py-1.5 text-sm font-semibold text-red-600 dark:text-red-400 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600"
                            >
                                Delete
                            </button>
                        </div>
                    </div>

                    <!-- Verification Instructions -->
                    <div v-if="!domain.is_verified" class="mt-6 border-t border-gray-100 dark:border-gray-700 pt-6">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-200 mb-4">DNS Configuration Required</h4>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- TXT Record -->
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="inline-flex items-center justify-center rounded-full bg-brand-100 dark:bg-brand-900/30 px-2 py-0.5 text-xs font-medium text-brand-700 dark:text-brand-400">Step 1</span>
                                    <span class="text-sm font-medium text-gray-800 dark:text-gray-200">Verify Ownership (TXT)</span>
                                </div>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-3">Add this TXT record to your DNS settings to prove you own the domain.</p>
                                
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between bg-white dark:bg-gray-800 p-2 rounded border border-gray-200 dark:border-gray-700 text-sm font-mono text-gray-800 dark:text-gray-200">
                                        <span class="text-gray-500">Type</span> TXT
                                    </div>
                                    <div class="flex items-center justify-between bg-white dark:bg-gray-800 p-2 rounded border border-gray-200 dark:border-gray-700 text-sm font-mono text-gray-800 dark:text-gray-200">
                                        <span class="text-gray-500">Name</span> @
                                    </div>
                                    <div class="flex items-center justify-between gap-4 bg-white dark:bg-gray-800 p-2 rounded border border-gray-200 dark:border-gray-700 text-sm font-mono text-gray-800 dark:text-gray-200 overflow-hidden">
                                        <div class="flex flex-col gap-1 overflow-hidden truncate">
                                            <span class="text-gray-500">Value</span>
                                            <span class="truncate">{{ domain.verification_token }}</span>
                                        </div>
                                        <CopyButton :value="domain.verification_token" size="sm" />
                                    </div>
                                </div>
                            </div>

                            <!-- CNAME Record -->
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="inline-flex items-center justify-center rounded-full bg-brand-100 dark:bg-brand-900/30 px-2 py-0.5 text-xs font-medium text-brand-700 dark:text-brand-400">Step 2</span>
                                    <span class="text-sm font-medium text-gray-800 dark:text-gray-200">Point Traffic (CNAME)</span>
                                </div>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-3">Add this CNAME record so your domain routes to our servers.</p>
                                
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between bg-white dark:bg-gray-800 p-2 rounded border border-gray-200 dark:border-gray-700 text-sm font-mono text-gray-800 dark:text-gray-200">
                                        <span class="text-gray-500">Type</span> CNAME
                                    </div>
                                    <div class="flex items-center justify-between bg-white dark:bg-gray-800 p-2 rounded border border-gray-200 dark:border-gray-700 text-sm font-mono text-gray-800 dark:text-gray-200">
                                        <span class="text-gray-500">Name</span> {{ domain.domain.split('.')[0] }}
                                    </div>
                                    <div class="flex items-center justify-between gap-4 bg-white dark:bg-gray-800 p-2 rounded border border-gray-200 dark:border-gray-700 text-sm font-mono text-gray-800 dark:text-gray-200 overflow-hidden">
                                        <div class="flex flex-col gap-1 overflow-hidden truncate">
                                            <span class="text-gray-500">Value</span>
                                            <span class="truncate">{{ dnsTarget }}</span>
                                        </div>
                                        <CopyButton :value="dnsTarget" size="sm" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <p class="text-xs text-gray-500 mt-4 italic">
                            Note: DNS changes can take up to 24-48 hours to propagate globally. You may need to wait a few minutes before verification succeeds.
                        </p>
                    </div>
                </div>

                <div v-if="domains.length === 0" class="text-center py-12 px-4 rounded-2xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200/60 dark:ring-gray-700/60">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="mx-auto h-12 w-12 text-gray-400"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <circle cx="12" cy="12" r="10" />
                        <path d="M2 12h20" />
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                    </svg>
                    <h3 class="mt-4 text-sm font-semibold text-gray-900 dark:text-white">No custom domains</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by adding your first custom domain above.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
