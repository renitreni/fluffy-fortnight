<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    showNav: {
        type: Boolean,
        default: true,
    },
});
</script>

<template>
    <div class="relative min-h-screen bg-white text-gray-900 dark:bg-gray-950 dark:text-gray-100 overflow-x-hidden flex flex-col">
        <!-- Background Effects -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none -z-10">
            <div class="absolute -top-[30%] -right-[10%] w-[70%] h-[70%] rounded-full bg-brand-500/5 blur-3xl animate-blob"></div>
            <div class="absolute top-[20%] -left-[20%] w-[60%] h-[60%] rounded-full bg-purple-500/5 blur-3xl animate-blob" style="animation-delay: 2s"></div>
            <div class="absolute top-[60%] right-[10%] w-[50%] h-[50%] rounded-full bg-pink-500/5 blur-3xl animate-blob" style="animation-delay: 4s"></div>
        </div>

        <!-- Navigation -->
        <nav v-if="showNav" class="sticky top-0 z-50 glass border-b border-gray-200/50 dark:border-gray-800/50">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <Link href="/" class="flex items-center gap-3">
                        <ApplicationLogo variant="mark-with-text" class="h-9 w-auto" />
                    </Link>

                    <div class="flex items-center gap-3">
                        <slot name="nav-action" />
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="flex-1 flex items-center justify-center px-4 sm:px-6 py-12 sm:py-16 lg:py-20">
            <div class="w-full max-w-md">
                <slot />
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-gray-200/50 dark:border-gray-800/50 bg-white dark:bg-gray-950">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-2.5">
                        <ApplicationLogo variant="wordmark" class="h-6 w-auto opacity-80" />
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-500">
                        &copy; {{ new Date().getFullYear() }} Elido. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
