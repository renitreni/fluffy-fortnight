<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import CopyButton from '@/Components/CopyButton.vue';
import LoadingSpinner from '@/Components/LoadingSpinner.vue';
import { useToastStore } from '@/Stores/useToastStore';

/**
 * Shorten.vue — The primary URL shortening interface.
 *
 * Receives from LinkController::index():
 *   - recentLinks: The authenticated user's 5 most recently created links.
 *   - appUrl:      The application base URL for constructing short link previews.
 *
 * On successful submission, the controller redirects back to this page with a
 * `flash` object containing the new short URL and metadata.
 */

const props = defineProps({
    recentLinks: {
        type: Array,
        default: () => [],
    },
    appUrl: {
        type: String,
        required: true,
    },
    customDomains: {
        type: Array,
        default: () => [],
    },
});

const toast = useToastStore();
const page = usePage();

// ── Form State ──────────────────────────────────────────────────────────────

const form = useForm({
    original_url: '',
    title: '',
    custom_alias: '',
    expires_at: '',
    password: '',
    ios_deep_link: '',
    android_deep_link: '',
    custom_domain_id: '',
    og_image: null,
});

const showAdvanced = ref(false);
const showUtmBuilder = ref(false);
const showPassword = ref(false);

// ── OG Image Upload ─────────────────────────────────────────────────────────

const ogImagePreview = ref(null);
const ogImageInputRef = ref(null);

function handleOgImageChange(event) {
    const file = event.target.files?.[0];
    if (!file) return;

    // Validate file type and size client-side for better UX
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    if (!allowedTypes.includes(file.type)) {
        toast.error('Only JPG and PNG images are allowed.');
        event.target.value = '';
        return;
    }
    if (file.size > 2 * 1024 * 1024) {
        toast.error('Image must be smaller than 2MB.');
        event.target.value = '';
        return;
    }

    form.og_image = file;
    ogImagePreview.value = URL.createObjectURL(file);
}

function removeOgImage() {
    form.og_image = null;
    ogImagePreview.value = null;
    if (ogImageInputRef.value) {
        ogImageInputRef.value.value = '';
    }
}

const utmParams = ref({
    utm_source: '',
    utm_medium: '',
    utm_campaign: '',
    utm_term: '',
    utm_content: '',
});

let isUpdatingUrl = false;
let isUpdatingUtms = false;

watch(
    () => form.original_url,
    (newUrl) => {
        if (isUpdatingUrl) return;
        try {
            let urlToParse = newUrl;
            if (urlToParse && !urlToParse.includes('://')) {
                urlToParse = 'https://' + urlToParse;
            }
            const urlObj = new URL(urlToParse);
            const params = new URLSearchParams(urlObj.search);

            isUpdatingUtms = true;
            let hasUtms = false;
            ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'].forEach((param) => {
                const val = params.get(param) || '';
                if (utmParams.value[param] !== val) {
                    utmParams.value[param] = val;
                }
                if (val) hasUtms = true;
            });

            if (hasUtms && !showUtmBuilder.value) {
                showUtmBuilder.value = true;
            }

            setTimeout(() => {
                isUpdatingUtms = false;
            }, 0);
        } catch (e) {
            // Invalid URL, do nothing
        }
    },
);

watch(
    utmParams,
    (newUtms) => {
        if (isUpdatingUtms) return;
        if (!form.original_url) return;

        try {
            isUpdatingUrl = true;
            let urlToParse = form.original_url;
            let missingScheme = false;
            if (!urlToParse.includes('://')) {
                missingScheme = true;
                urlToParse = 'https://' + urlToParse;
            }

            const urlObj = new URL(urlToParse);
            const params = new URLSearchParams(urlObj.search);

            Object.keys(newUtms).forEach((key) => {
                if (newUtms[key]) {
                    params.set(key, newUtms[key]);
                } else {
                    params.delete(key);
                }
            });

            urlObj.search = params.toString();
            let finalUrl = urlObj.toString();
            if (missingScheme) {
                finalUrl = finalUrl.replace(/^https:\/\//, '');
            }

            form.original_url = finalUrl;
            setTimeout(() => {
                isUpdatingUrl = false;
            }, 0);
        } catch (e) {
            // Unparseable URL, do nothing
        }
    },
    { deep: true },
);

const selectedDomainText = computed(() => {
    if (!form.custom_domain_id) return props.appUrl.replace(/^https?:\/\//, '');
    const domain = props.customDomains.find(d => d.id === form.custom_domain_id);
    return domain ? domain.domain : props.appUrl.replace(/^https?:\/\//, '');
});

// ── Flash / Result State ─────────────────────────────────────────────────────

const result = ref(null);

// Watch for flash data from the controller redirect
watch(
    () => page.props.flash,
    (flash) => {
        if (!flash) return;
        result.value = flash;
        if (flash.type === 'success') {
            toast.success(flash.message);
        } else if (flash.type === 'info') {
            toast.info(flash.message);
        }
    },
    { immediate: true },
);

// ── Recent Links ─────────────────────────────────────────────────────────────

const recentLinks = ref([...props.recentLinks]);

// After a successful creation, the page re-renders with updated props
watch(
    () => props.recentLinks,
    (val) => {
        recentLinks.value = [...val];
    },
);

// ── Helpers ──────────────────────────────────────────────────────────────────

function shortUrl(code) {
    return `${props.appUrl}/${code}`;
}

function relativeTime(dateStr) {
    const date = new Date(dateStr);
    const seconds = Math.floor((Date.now() - date.getTime()) / 1000);

    if (seconds < 60) return 'just now';
    if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
    return `${Math.floor(seconds / 86400)}d ago`;
}

function truncate(str, max = 50) {
    if (!str) return '';
    return str.length > max ? str.slice(0, max) + '…' : str;
}

// ── Submission ───────────────────────────────────────────────────────────────

function submit() {
    result.value = null;
    form.post(route('links.store'), {
        preserveScroll: true,
        onError: () => {
            toast.error('Please fix the errors below.');
        },
    });
}

// ── Animation trigger ────────────────────────────────────────────────────────

const mounted = ref(false);
onMounted(() => {
    mounted.value = true;
});
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <div
                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-purple-600 shadow-lg shadow-brand-500/30"
                >
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
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">Shorten a URL</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Create a short, shareable link in seconds</p>
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
            <!-- ── Main Shorten Card ───────────────────────────────────────── -->
            <div
                class="relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-xl shadow-gray-200/50 dark:shadow-none ring-1 ring-gray-200/60 dark:ring-gray-700/60"
            >
                <!-- Gradient accent bar -->
                <div
                    class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-brand-500 via-purple-500 to-pink-500"
                ></div>

                <div class="p-6 sm:p-8">
                    <form id="shorten-form" @submit.prevent="submit" novalidate>
                        <!-- URL Input -->
                        <div class="space-y-2">
                            <label
                                for="original_url"
                                class="block text-sm font-semibold text-gray-700 dark:text-gray-300"
                            >
                                Long URL
                            </label>

                            <div
                                class="url-input-wrapper"
                                :class="{ 'url-input-wrapper--error': form.errors.original_url }"
                            >
                                <!-- Link icon -->
                                <div class="url-input-icon">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
                                    </svg>
                                </div>

                                <input
                                    id="original_url"
                                    v-model="form.original_url"
                                    type="url"
                                    name="original_url"
                                    placeholder="https://example.com/very/long/url?with=params"
                                    autocomplete="off"
                                    spellcheck="false"
                                    class="url-input"
                                    :disabled="form.processing"
                                    @keydown.enter.prevent="submit"
                                />

                                <!-- Paste button -->
                                <button
                                    id="paste-btn"
                                    type="button"
                                    class="paste-btn"
                                    title="Paste from clipboard"
                                    :disabled="form.processing"
                                    @click="
                                        async () => {
                                            try {
                                                form.original_url = await navigator.clipboard.readText();
                                            } catch {}
                                        }
                                    "
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-4 w-4"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2" />
                                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
                                    </svg>
                                    <span>Paste</span>
                                </button>
                            </div>

                            <InputError :message="form.errors.original_url" />
                        </div>

                        <!-- Optional advanced options and UTM builder toggles -->
                        <div class="mt-3 flex flex-wrap items-center gap-6">
                            <button
                                id="toggle-advanced-btn"
                                type="button"
                                class="inline-flex items-center gap-1.5 text-xs text-brand-600 dark:text-brand-400 hover:text-brand-700 font-medium transition-colors"
                                @click="showAdvanced = !showAdvanced"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-3.5 w-3.5 transition-transform duration-200"
                                    :class="{ 'rotate-45': showAdvanced }"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <circle cx="12" cy="12" r="10" />
                                    <line x1="12" y1="8" x2="12" y2="16" />
                                    <line x1="8" y1="12" x2="16" y2="12" />
                                </svg>
                                {{ showAdvanced ? 'Hide advanced options' : 'Advanced options (Title, Custom Alias)' }}
                            </button>

                            <button
                                id="toggle-utm-btn"
                                type="button"
                                class="inline-flex items-center gap-1.5 text-xs text-brand-600 dark:text-brand-400 hover:text-brand-700 font-medium transition-colors"
                                @click="showUtmBuilder = !showUtmBuilder"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-3.5 w-3.5 transition-transform duration-200"
                                    :class="{ 'rotate-45': showUtmBuilder }"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <circle cx="12" cy="12" r="10" />
                                    <line x1="12" y1="8" x2="12" y2="16" />
                                    <line x1="8" y1="12" x2="16" y2="12" />
                                </svg>
                                {{ showUtmBuilder ? 'Hide UTM Builder' : 'UTM Builder' }}
                            </button>
                        </div>

                        <!-- Advanced fields (collapsible) -->
                        <Transition name="slide-down">
                            <div v-if="showAdvanced" class="mt-4 space-y-4">
                                <!-- Title -->
                                <div class="space-y-2">
                                    <label
                                        for="link_title"
                                        class="block text-sm font-semibold text-gray-700 dark:text-gray-300"
                                    >
                                        Title <span class="text-xs text-gray-500 font-normal">(optional)</span>
                                    </label>
                                    <input
                                        id="link_title"
                                        v-model="form.title"
                                        type="text"
                                        name="title"
                                        placeholder="e.g. My Product Launch"
                                        maxlength="255"
                                        class="title-input"
                                        :disabled="form.processing"
                                    />
                                    <InputError :message="form.errors.title" />
                                </div>

                                <!-- Custom Domain -->
                                <div class="space-y-2" v-if="customDomains.length > 0">
                                    <label
                                        for="custom_domain_id"
                                        class="block text-sm font-semibold text-gray-700 dark:text-gray-300"
                                    >
                                        Domain <span class="text-xs text-gray-500 font-normal">(optional)</span>
                                    </label>
                                    <select
                                        id="custom_domain_id"
                                        v-model="form.custom_domain_id"
                                        class="title-input w-full"
                                        :disabled="form.processing"
                                    >
                                        <option value="">{{ props.appUrl.replace(/^https?:\/\//, '') }} (Default)</option>
                                        <option v-for="domain in customDomains" :key="domain.id" :value="domain.id">
                                            {{ domain.domain }}
                                        </option>
                                    </select>
                                    <InputError :message="form.errors.custom_domain_id" />
                                </div>

                                <!-- Custom Alias -->
                                <div class="space-y-2">
                                    <label
                                        for="custom_alias"
                                        class="block text-sm font-semibold text-gray-700 dark:text-gray-300"
                                    >
                                        Custom Alias <span class="text-xs text-gray-500 font-normal">(optional)</span>
                                    </label>
                                    <div
                                        class="alias-input-wrapper"
                                        :class="{ 'alias-input-wrapper--error': form.errors.custom_alias }"
                                    >
                                        <span class="alias-prefix"
                                            >{{ selectedDomainText }}/</span
                                        >
                                        <input
                                            id="custom_alias"
                                            v-model="form.custom_alias"
                                            type="text"
                                            name="custom_alias"
                                            placeholder="my-custom-url"
                                            maxlength="255"
                                            autocomplete="off"
                                            class="alias-input"
                                            :disabled="form.processing"
                                            @keydown.enter.prevent="submit"
                                        />
                                    </div>
                                    <InputError :message="form.errors.custom_alias" />
                                </div>

                                <!-- Expiration Date -->
                                <div class="space-y-2">
                                    <label
                                        for="expires_at"
                                        class="block text-sm font-semibold text-gray-700 dark:text-gray-300"
                                    >
                                        Expiration Date
                                        <span class="text-xs text-gray-500 font-normal">(optional)</span>
                                    </label>
                                    <input
                                        id="expires_at"
                                        v-model="form.expires_at"
                                        type="datetime-local"
                                        name="expires_at"
                                        class="title-input"
                                        :disabled="form.processing"
                                    />
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Link will return 410 Gone after this date. Leave empty for no expiry.
                                    </p>
                                    <InputError :message="form.errors.expires_at" />
                                </div>

                                <!-- Password Protection -->
                                <div class="space-y-2">
                                    <label
                                        for="link_password"
                                        class="block text-sm font-semibold text-gray-700 dark:text-gray-300"
                                    >
                                        Password Protection
                                        <span class="text-xs text-gray-500 font-normal">(optional)</span>
                                    </label>
                                    <div class="relative">
                                        <input
                                            id="link_password"
                                            v-model="form.password"
                                            :type="showPassword ? 'text' : 'password'"
                                            name="password"
                                            placeholder="Min. 4 characters"
                                            maxlength="72"
                                            autocomplete="new-password"
                                            class="title-input pr-12"
                                            :disabled="form.processing"
                                        />
                                        <button
                                            type="button"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors p-1 rounded"
                                            :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                            @click="showPassword = !showPassword"
                                        >
                                            <svg
                                                v-if="!showPassword"
                                                class="w-5 h-5"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                                stroke-width="1.5"
                                                aria-hidden="true"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"
                                                />
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                                />
                                            </svg>
                                            <svg
                                                v-else
                                                class="w-5 h-5"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                                stroke-width="1.5"
                                                aria-hidden="true"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Visitors must enter this password to access the link.
                                    </p>
                                    <InputError :message="form.errors.password" />
                                </div>

                                <!-- OG Image Upload -->
                                <div class="space-y-2 pt-4 border-t border-gray-100 dark:border-gray-700">
                                    <label
                                        for="og_image"
                                        class="block text-sm font-semibold text-gray-700 dark:text-gray-300"
                                    >
                                        Social Preview Image
                                        <span class="text-xs text-gray-500 font-normal">(optional)</span>
                                    </label>

                                    <!-- Image Preview -->
                                    <div v-if="ogImagePreview" class="og-image-preview-wrapper">
                                        <img
                                            :src="ogImagePreview"
                                            alt="OG Image Preview"
                                            class="og-image-preview"
                                        />
                                        <button
                                            type="button"
                                            class="og-image-remove-btn"
                                            title="Remove image"
                                            @click="removeOgImage"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="h-4 w-4"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            >
                                                <line x1="18" y1="6" x2="6" y2="18" />
                                                <line x1="6" y1="6" x2="18" y2="18" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- File Input -->
                                    <div
                                        v-else
                                        class="og-image-dropzone"
                                        :class="{ 'og-image-dropzone--error': form.errors.og_image }"
                                    >
                                        <input
                                            id="og_image"
                                            ref="ogImageInputRef"
                                            type="file"
                                            name="og_image"
                                            accept=".jpg,.jpeg,.png"
                                            class="og-image-input"
                                            :disabled="form.processing"
                                            @change="handleOgImageChange"
                                        />
                                        <div class="og-image-dropzone-content">
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="h-8 w-8 text-gray-400 mb-2"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="1.5"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            >
                                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                                                <circle cx="8.5" cy="8.5" r="1.5" />
                                                <polyline points="21 15 16 10 5 21" />
                                            </svg>
                                            <p class="text-sm text-gray-600 dark:text-gray-300 font-medium">
                                                Click to upload or drag and drop
                                            </p>
                                            <p class="text-xs text-gray-400 mt-1">
                                                JPG or PNG, max 2MB
                                            </p>
                                        </div>
                                    </div>
                                    <InputError :message="form.errors.og_image" />
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        This image will appear when the link is shared on Facebook, Twitter, LinkedIn, etc.
                                    </p>
                                </div>

                                <!-- Mobile Deep Links -->
                                <div class="space-y-4 pt-4 border-t border-gray-100 dark:border-gray-700 mt-6">
                                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                                        Mobile Deep Links
                                        <span class="text-xs text-gray-500 font-normal">(optional)</span>
                                    </h3>

                                    <!-- iOS Deep Link -->
                                    <div class="space-y-2">
                                        <label
                                            for="ios_deep_link"
                                            class="block text-xs font-medium text-gray-700 dark:text-gray-300"
                                        >
                                            iOS Deep Link
                                        </label>
                                        <input
                                            id="ios_deep_link"
                                            v-model="form.ios_deep_link"
                                            type="text"
                                            name="ios_deep_link"
                                            placeholder="twitter://user?id=123"
                                            maxlength="2048"
                                            class="title-input"
                                            :disabled="form.processing"
                                        />
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            iOS users will be redirected to this app URI instead of the long URL.
                                        </p>
                                        <InputError :message="form.errors.ios_deep_link" />
                                    </div>

                                    <!-- Android Deep Link -->
                                    <div class="space-y-2">
                                        <label
                                            for="android_deep_link"
                                            class="block text-xs font-medium text-gray-700 dark:text-gray-300"
                                        >
                                            Android Deep Link
                                        </label>
                                        <input
                                            id="android_deep_link"
                                            v-model="form.android_deep_link"
                                            type="text"
                                            name="android_deep_link"
                                            placeholder="twitter://user?id=123"
                                            maxlength="2048"
                                            class="title-input"
                                            :disabled="form.processing"
                                        />
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Android users will be redirected to this app URI instead of the long URL.
                                        </p>
                                        <InputError :message="form.errors.android_deep_link" />
                                    </div>
                                </div>
                            </div>
                        </Transition>

                        <!-- UTM Builder (collapsible) -->
                        <Transition name="slide-down">
                            <div
                                v-if="showUtmBuilder"
                                class="mt-4 p-4 rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 space-y-4"
                            >
                                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">
                                    UTM Parameters
                                </h3>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="space-y-1.5">
                                        <label
                                            for="utm_source"
                                            class="block text-xs font-medium text-gray-700 dark:text-gray-300"
                                        >
                                            Source
                                            <span class="text-gray-400 font-normal">(e.g. google, newsletter)</span>
                                        </label>
                                        <input
                                            id="utm_source"
                                            v-model="utmParams.utm_source"
                                            type="text"
                                            class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                            :disabled="form.processing"
                                        />
                                    </div>
                                    <div class="space-y-1.5">
                                        <label
                                            for="utm_medium"
                                            class="block text-xs font-medium text-gray-700 dark:text-gray-300"
                                        >
                                            Medium <span class="text-gray-400 font-normal">(e.g. cpc, email)</span>
                                        </label>
                                        <input
                                            id="utm_medium"
                                            v-model="utmParams.utm_medium"
                                            type="text"
                                            class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                            :disabled="form.processing"
                                        />
                                    </div>
                                    <div class="space-y-1.5">
                                        <label
                                            for="utm_campaign"
                                            class="block text-xs font-medium text-gray-700 dark:text-gray-300"
                                        >
                                            Campaign <span class="text-gray-400 font-normal">(e.g. summer_sale)</span>
                                        </label>
                                        <input
                                            id="utm_campaign"
                                            v-model="utmParams.utm_campaign"
                                            type="text"
                                            class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                            :disabled="form.processing"
                                        />
                                    </div>
                                    <div class="space-y-1.5">
                                        <label
                                            for="utm_term"
                                            class="block text-xs font-medium text-gray-700 dark:text-gray-300"
                                        >
                                            Term <span class="text-gray-400 font-normal">(optional)</span>
                                        </label>
                                        <input
                                            id="utm_term"
                                            v-model="utmParams.utm_term"
                                            type="text"
                                            class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                            :disabled="form.processing"
                                        />
                                    </div>
                                    <div class="space-y-1.5 sm:col-span-2">
                                        <label
                                            for="utm_content"
                                            class="block text-xs font-medium text-gray-700 dark:text-gray-300"
                                        >
                                            Content <span class="text-gray-400 font-normal">(optional)</span>
                                        </label>
                                        <input
                                            id="utm_content"
                                            v-model="utmParams.utm_content"
                                            type="text"
                                            class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                            :disabled="form.processing"
                                        />
                                    </div>
                                </div>
                            </div>
                        </Transition>

                        <!-- Submit button -->
                        <div class="mt-6">
                            <button
                                id="shorten-submit-btn"
                                type="submit"
                                class="shorten-btn"
                                :disabled="form.processing || !form.original_url.trim()"
                            >
                                <LoadingSpinner v-if="form.processing" size="sm" variant="white" />
                                <svg
                                    v-else
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                {{ form.processing ? 'Shortening…' : 'Shorten URL' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ── Result Card ─────────────────────────────────────────────── -->
            <Transition name="result-pop">
                <div
                    v-if="result"
                    id="shorten-result-card"
                    class="result-card"
                    :class="result.reused ? 'result-card--info' : 'result-card--success'"
                >
                    <!-- Icon -->
                    <div class="result-card__icon">
                        <svg
                            v-if="result.reused"
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-6 w-6"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                        <svg
                            v-else
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-6 w-6"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                            <polyline points="22 4 12 14.01 9 11.01" />
                        </svg>
                    </div>

                    <div class="result-card__content">
                        <p class="result-card__label">
                            {{ result.reused ? 'Existing link reused' : 'Short link created!' }}
                        </p>

                        <!-- Short URL display + copy -->
                        <div class="result-card__url-row">
                            <a
                                :href="result.link"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="result-card__short-url"
                                :id="`short-url-${result.shortCode}`"
                            >
                                {{ result.link }}
                            </a>
                            <CopyButton
                                :value="result.link"
                                variant="solid"
                                size="md"
                                show-label
                                label="Copy"
                                :id="`copy-btn-${result.shortCode}`"
                                @copied="toast.success('Short URL copied to clipboard!')"
                            />
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- ── Recent Links ───────────────────────────────────────────── -->
            <div v-if="recentLinks.length > 0" class="recent-links">
                <h2 class="recent-links__heading">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 6 12 12 16 14" />
                    </svg>
                    Recent links
                </h2>

                <div class="recent-links__list">
                    <div v-for="link in recentLinks" :key="link.id" class="recent-link-item">
                        <div class="recent-link-item__info">
                            <a
                                :href="shortUrl(link.short_code)"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="recent-link-item__short"
                                :id="`recent-link-${link.short_code}`"
                            >
                                /{{ link.short_code }}
                            </a>
                            <span class="recent-link-item__original" :title="link.original_url">
                                {{ truncate(link.original_url, 60) }}
                            </span>
                        </div>

                        <div class="recent-link-item__meta">
                            <span class="recent-link-item__clicks">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-3.5 w-3.5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />
                                </svg>
                                {{ link.click_count ?? 0 }}
                            </span>
                            <span class="recent-link-item__time">{{ relativeTime(link.created_at) }}</span>
                            <CopyButton
                                :value="shortUrl(link.short_code)"
                                variant="ghost"
                                size="sm"
                                :id="`copy-recent-${link.short_code}`"
                                @copied="toast.success('Copied!')"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Empty state ───────────────────────────────────────────── -->
            <div v-else class="empty-state">
                <div class="empty-state__icon">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-8 w-8"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
                    </svg>
                </div>
                <p class="empty-state__text">Your shortened links will appear here</p>
                <p class="empty-state__sub">Paste any long URL above and hit Shorten URL</p>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* ── URL Input ──────────────────────────────────────────────────────────── */
.url-input-wrapper {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0 0.75rem;
    background: #f9fafb;
    border: 1.5px solid #e5e7eb;
    border-radius: 0.875rem;
    transition:
        border-color 0.2s,
        box-shadow 0.2s;
}

:global(.dark) .url-input-wrapper {
    background: #111827;
    border-color: #374151;
}

.url-input-wrapper:focus-within {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}

.url-input-wrapper--error {
    border-color: #f87171;
}

.url-input-icon {
    color: #9ca3af;
    display: flex;
    align-items: center;
    flex-shrink: 0;
}

.url-input {
    flex: 1;
    padding: 0.875rem 0;
    background: transparent;
    border: none;
    outline: none;
    font-size: 0.9375rem;
    color: #111827;
    min-width: 0;
}

:global(.dark) .url-input {
    color: #f9fafb;
}
.url-input::placeholder {
    color: #9ca3af;
}
.url-input:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.paste-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.375rem 0.75rem;
    background: #ede9fe;
    color: #6366f1;
    border: none;
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    font-weight: 600;
    cursor: pointer;
    flex-shrink: 0;
    transition: background 0.15s;
}

.paste-btn:hover {
    background: #ddd6fe;
}
:global(.dark) .paste-btn {
    background: rgba(99, 102, 241, 0.15);
    color: #a5b4fc;
}
:global(.dark) .paste-btn:hover {
    background: rgba(99, 102, 241, 0.25);
}

/* ── Title Input ────────────────────────────────────────────────────────── */
.title-input {
    width: 100%;
    padding: 0.75rem 1rem;
    background: #f9fafb;
    border: 1.5px solid #e5e7eb;
    border-radius: 0.75rem;
    font-size: 0.9375rem;
    color: #111827;
    outline: none;
    transition:
        border-color 0.2s,
        box-shadow 0.2s;
}

:global(.dark) .title-input {
    background: #111827;
    border-color: #374151;
    color: #f9fafb;
}
.title-input:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}
.title-input::placeholder {
    color: #9ca3af;
}

/* ── Custom Alias Input ─────────────────────────────────────────────────── */
.alias-input-wrapper {
    display: flex;
    align-items: center;
    background: #f9fafb;
    border: 1.5px solid #e5e7eb;
    border-radius: 0.75rem;
    overflow: hidden;
    transition:
        border-color 0.2s,
        box-shadow 0.2s;
}

:global(.dark) .alias-input-wrapper {
    background: #111827;
    border-color: #374151;
}

.alias-input-wrapper:focus-within {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}

.alias-input-wrapper--error {
    border-color: #f87171;
}

.alias-prefix {
    padding: 0.75rem 0.5rem 0.75rem 1rem;
    background: #f3f4f6;
    color: #6b7280;
    font-size: 0.9375rem;
    border-right: 1.5px solid #e5e7eb;
    user-select: none;
}

:global(.dark) .alias-prefix {
    background: #1f2937;
    color: #9ca3af;
    border-right-color: #374151;
}

.alias-input {
    flex: 1;
    padding: 0.75rem 1rem 0.75rem 0.5rem;
    background: transparent;
    border: none;
    outline: none;
    font-size: 0.9375rem;
    color: #111827;
    min-width: 0;
}

:global(.dark) .alias-input {
    color: #f9fafb;
}
.alias-input::placeholder {
    color: #9ca3af;
}
.alias-input:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* ── Shorten Button ─────────────────────────────────────────────────────── */
.shorten-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.875rem 1.5rem;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a855f7 100%);
    color: white;
    font-size: 1rem;
    font-weight: 700;
    border: none;
    border-radius: 0.875rem;
    cursor: pointer;
    letter-spacing: 0.01em;
    box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
    transition: all 0.2s ease;
}

.shorten-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(99, 102, 241, 0.5);
}

.shorten-btn:active:not(:disabled) {
    transform: translateY(0);
}

.shorten-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

/* ── Result Card ────────────────────────────────────────────────────────── */
.result-card {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1.25rem 1.5rem;
    border-radius: 1rem;
    border: 1.5px solid;
}

.result-card--success {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.08) 0%, rgba(5, 150, 105, 0.04) 100%);
    border-color: rgba(16, 185, 129, 0.3);
    color: #065f46;
}

:global(.dark) .result-card--success {
    background: rgba(16, 185, 129, 0.1);
    border-color: rgba(16, 185, 129, 0.25);
    color: #6ee7b7;
}

.result-card--info {
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.08) 0%, rgba(139, 92, 246, 0.04) 100%);
    border-color: rgba(99, 102, 241, 0.3);
    color: #3730a3;
}

:global(.dark) .result-card--info {
    background: rgba(99, 102, 241, 0.1);
    border-color: rgba(99, 102, 241, 0.25);
    color: #a5b4fc;
}

.result-card__icon {
    margin-top: 0.125rem;
    flex-shrink: 0;
}

.result-card__content {
    flex: 1;
    min-width: 0;
}

.result-card__label {
    font-size: 0.8125rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 0.5rem;
    opacity: 0.7;
}

.result-card__url-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.result-card__short-url {
    font-size: 1.125rem;
    font-weight: 700;
    text-decoration: none;
    word-break: break-all;
}

.result-card--success .result-card__short-url {
    color: #059669;
}
.result-card--info .result-card__short-url {
    color: #6366f1;
}
:global(.dark) .result-card--success .result-card__short-url {
    color: #34d399;
}
:global(.dark) .result-card--info .result-card__short-url {
    color: #818cf8;
}

.result-card__short-url:hover {
    text-decoration: underline;
}

/* ── Recent Links ────────────────────────────────────────────────────────── */
.recent-links {
}

.recent-links__heading {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 0.75rem;
}

.recent-links__list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.recent-link-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.875rem 1.25rem;
    background: white;
    border: 1px solid #f3f4f6;
    border-radius: 0.875rem;
    transition: all 0.15s;
}

:global(.dark) .recent-link-item {
    background: #1f2937;
    border-color: #374151;
}

.recent-link-item:hover {
    border-color: #e5e7eb;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

:global(.dark) .recent-link-item:hover {
    border-color: #4b5563;
}

.recent-link-item__info {
    flex: 1;
    min-width: 0;
}

.recent-link-item__short {
    display: block;
    font-size: 0.9375rem;
    font-weight: 700;
    color: #6366f1;
    text-decoration: none;
    margin-bottom: 0.125rem;
}

.recent-link-item__short:hover {
    text-decoration: underline;
}
:global(.dark) .recent-link-item__short {
    color: #818cf8;
}

.recent-link-item__original {
    display: block;
    font-size: 0.8rem;
    color: #9ca3af;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.recent-link-item__meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-shrink: 0;
}

.recent-link-item__clicks {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.8125rem;
    color: #9ca3af;
}

.recent-link-item__time {
    font-size: 0.8125rem;
    color: #9ca3af;
    white-space: nowrap;
}

/* ── Empty State ─────────────────────────────────────────────────────────── */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 1.5rem;
    text-align: center;
}

.empty-state__icon {
    width: 4rem;
    height: 4rem;
    border-radius: 1rem;
    background: linear-gradient(135deg, #ede9fe, #ddd6fe);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6366f1;
    margin-bottom: 1rem;
}

.empty-state__text {
    font-size: 1rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.375rem;
}

:global(.dark) .empty-state__text {
    color: #d1d5db;
}

.empty-state__sub {
    font-size: 0.875rem;
    color: #9ca3af;
}

/* ── OG Image Upload ────────────────────────────────────────────────────── */
.og-image-preview-wrapper {
    position: relative;
    display: inline-block;
    border-radius: 0.75rem;
    overflow: hidden;
    border: 1.5px solid #e5e7eb;
}

:global(.dark) .og-image-preview-wrapper {
    border-color: #374151;
}

.og-image-preview {
    display: block;
    max-width: 100%;
    max-height: 200px;
    object-fit: cover;
}

.og-image-remove-btn {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 1.75rem;
    height: 1.75rem;
    background: rgba(0, 0, 0, 0.6);
    color: white;
    border: none;
    border-radius: 9999px;
    cursor: pointer;
    transition: background 0.15s;
}

.og-image-remove-btn:hover {
    background: rgba(0, 0, 0, 0.8);
}

.og-image-dropzone {
    position: relative;
    border: 2px dashed #d1d5db;
    border-radius: 0.75rem;
    padding: 1.5rem;
    text-align: center;
    transition: border-color 0.2s, background 0.2s;
    cursor: pointer;
}

:global(.dark) .og-image-dropzone {
    border-color: #4b5563;
}

.og-image-dropzone:hover {
    border-color: #6366f1;
    background: rgba(99, 102, 241, 0.04);
}

:global(.dark) .og-image-dropzone:hover {
    background: rgba(99, 102, 241, 0.08);
}

.og-image-dropzone--error {
    border-color: #f87171;
    background: rgba(248, 113, 113, 0.04);
}

.og-image-input {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

.og-image-dropzone-content {
    pointer-events: none;
    display: flex;
    flex-direction: column;
    align-items: center;
}

/* ── Transitions ─────────────────────────────────────────────────────────── */
.slide-down-enter-active,
.slide-down-leave-active {
    transition: all 0.25s ease;
    overflow: hidden;
}

.slide-down-enter-from,
.slide-down-leave-to {
    opacity: 0;
    max-height: 0;
    transform: translateY(-4px);
}

.slide-down-enter-to,
.slide-down-leave-from {
    opacity: 1;
    max-height: 200px;
}

.result-pop-enter-active {
    animation: result-pop-in 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.result-pop-leave-active {
    transition: all 0.2s ease;
}
.result-pop-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}

@keyframes result-pop-in {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(8px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}
</style>
