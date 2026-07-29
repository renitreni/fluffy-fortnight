<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    bioPage: {
        type: Object,
        default: () => ({
            id: null,
            alias: '',
            title: '',
            description: '',
            theme: 'light',
            links: [],
        })
    },
    availableLinks: Array,
});

const isEditing = !!props.bioPage.id;

// Initialize links array from relationship
const initialLinks = props.bioPage.links ? props.bioPage.links.map(l => l.id) : [];

const form = useForm({
    alias: props.bioPage.alias,
    title: props.bioPage.title,
    description: props.bioPage.description,
    theme: props.bioPage.theme,
    links: initialLinks,
});

const submit = () => {
    if (isEditing) {
        form.put(route('bio-pages.update', props.bioPage.id));
    } else {
        form.post(route('bio-pages.store'));
    }
};

const toggleLink = (linkId) => {
    const index = form.links.indexOf(linkId);
    if (index === -1) {
        form.links.push(linkId);
    } else {
        form.links.splice(index, 1);
    }
};
</script>

<template>
    <Head :title="isEditing ? 'Edit Bio Page' : 'Create Bio Page'" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ isEditing ? 'Edit Bio Page' : 'Create Bio Page' }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <form @submit.prevent="submit" class="space-y-6 max-w-2xl">
                        <!-- Alias -->
                        <div>
                            <InputLabel for="alias" value="Alias (URL slug)" />
                            <TextInput
                                id="alias"
                                v-model="form.alias"
                                type="text"
                                class="mt-1 block w-full"
                                required
                                autofocus
                            />
                            <p class="mt-1 text-sm text-gray-500">This will be your public URL: /b/{{ form.alias || 'your-alias' }}</p>
                            <InputError :message="form.errors.alias" class="mt-2" />
                        </div>

                        <!-- Title -->
                        <div>
                            <InputLabel for="title" value="Page Title" />
                            <TextInput
                                id="title"
                                v-model="form.title"
                                type="text"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.title" class="mt-2" />
                        </div>

                        <!-- Description -->
                        <div>
                            <InputLabel for="description" value="Description (Optional)" />
                            <textarea
                                id="description"
                                v-model="form.description"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 dark:focus:border-brand-600 focus:ring-brand-500 dark:focus:ring-brand-600 rounded-md shadow-sm"
                                rows="3"
                            ></textarea>
                            <InputError :message="form.errors.description" class="mt-2" />
                        </div>

                        <!-- Theme -->
                        <div>
                            <InputLabel for="theme" value="Theme" />
                            <select
                                id="theme"
                                v-model="form.theme"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-500 dark:focus:border-brand-600 focus:ring-brand-500 dark:focus:ring-brand-600 rounded-md shadow-sm"
                            >
                                <option value="light">Light</option>
                                <option value="dark">Dark</option>
                                <option value="brand">Brand</option>
                            </select>
                            <InputError :message="form.errors.theme" class="mt-2" />
                        </div>

                        <!-- Links Selection -->
                        <div>
                            <InputLabel value="Attach Links" class="mb-2" />
                            <div class="space-y-2 max-h-64 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-md p-3">
                                <div v-if="availableLinks.length === 0" class="text-gray-500 text-sm">
                                    You don't have any links to attach. Create short links first.
                                </div>
                                <label v-for="link in availableLinks" :key="link.id" class="flex items-center space-x-3 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        :value="link.id"
                                        :checked="form.links.includes(link.id)"
                                        @change="toggleLink(link.id)"
                                        class="rounded border-gray-300 text-brand-600 shadow-sm focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-brand-600"
                                    />
                                    <span class="text-sm text-gray-700 dark:text-gray-300 truncate">
                                        {{ link.title || link.original_url }} <span class="text-gray-500 text-xs">(/{{ link.short_code }})</span>
                                    </span>
                                </label>
                            </div>
                            <InputError :message="form.errors.links" class="mt-2" />
                            <p class="text-xs text-gray-500 mt-2">Check the links you want to display on your bio page.</p>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                {{ isEditing ? 'Update Bio Page' : 'Create Bio Page' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
