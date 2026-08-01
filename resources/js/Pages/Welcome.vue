<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

defineProps({
    canLogin: { type: Boolean },
    canRegister: { type: Boolean },
});

// ── Scroll Animation Observer ───────────────────────────────────────────────

const observeElements = () => {
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-visible');
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.1, rootMargin: '0px 0px -50px 0px' }
    );

    document.querySelectorAll('.animate-on-scroll').forEach((el) => {
        observer.observe(el);
    });
};

// ── Stats Counter Animation ─────────────────────────────────────────────────

const stats = ref([
    { label: 'Links Shortened', value: 0, target: 12500000, suffix: '+' },
    { label: 'Total Clicks Tracked', value: 0, target: 890000000, suffix: '+' },
    { label: 'Active Users', value: 0, target: 52000, suffix: '+' },
    { label: 'Uptime Guarantee', value: 0, target: 99.9, suffix: '%' },
]);

const statsAnimated = ref(false);

const animateStats = () => {
    if (statsAnimated.value) return;
    statsAnimated.value = true;

    stats.value.forEach((stat, index) => {
        const duration = 2000;
        const startTime = performance.now();
        const delay = index * 150;

        setTimeout(() => {
            const step = (currentTime) => {
                const elapsed = currentTime - startTime - delay;
                const progress = Math.min(elapsed / duration, 1);
                const easeOut = 1 - Math.pow(1 - progress, 3);
                stat.value = Math.floor(stat.target * easeOut);

                if (progress < 1) {
                    requestAnimationFrame(step);
                } else {
                    stat.value = stat.target;
                }
            };
            requestAnimationFrame(step);
        }, delay);
    });
};

// ── Hero Demo Shortener ─────────────────────────────────────────────────────

const demoUrl = ref('');
const demoResult = ref(null);
const demoProcessing = ref(false);

const formatNumber = (num) => {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    }
    if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toLocaleString();
};

const handleDemoShorten = () => {
    if (!demoUrl.value.trim()) return;
    demoProcessing.value = true;
    demoResult.value = null;

    // Simulate processing for demo effect
    setTimeout(() => {
        const code = Math.random().toString(36).substring(2, 8);
        demoResult.value = {
            shortUrl: `${window.location.origin}/${code}`,
            originalUrl: demoUrl.value,
        };
        demoProcessing.value = false;
    }, 800);
};

const copyDemoUrl = async () => {
    if (!demoResult.value) return;
    try {
        await navigator.clipboard.writeText(demoResult.value.shortUrl);
        // Show copied feedback
        const btn = document.getElementById('demo-copy-btn');
        if (btn) {
            const original = btn.innerHTML;
            btn.innerHTML = `<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg><span>Copied!</span>`;
            setTimeout(() => { btn.innerHTML = original; }, 2000);
        }
    } catch (err) {
        // Silently fail
    }
};

// ── Mobile Menu ─────────────────────────────────────────────────────────────

const mobileMenuOpen = ref(false);

// ── Lifecycle ───────────────────────────────────────────────────────────────

onMounted(() => {
    observeElements();

    // Observe stats section for counter animation
    const statsSection = document.getElementById('stats-section');
    if (statsSection) {
        const statsObserver = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        animateStats();
                        statsObserver.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.3 }
        );
        statsObserver.observe(statsSection);
    }
});

onUnmounted(() => {
    // Cleanup if needed
});
</script>

<template>
    <Head title="Elido — Shorten Links, Track Clicks, Grow Faster" />

    <div class="relative min-h-screen bg-white text-gray-900 dark:bg-gray-950 dark:text-gray-100 overflow-x-hidden">
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <!-- BACKGROUND EFFECTS                                              -->
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none -z-10">
            <div class="absolute -top-[30%] -right-[10%] w-[70%] h-[70%] rounded-full bg-brand-500/5 blur-3xl animate-blob"></div>
            <div class="absolute top-[20%] -left-[20%] w-[60%] h-[60%] rounded-full bg-purple-500/5 blur-3xl animate-blob" style="animation-delay: 2s"></div>
            <div class="absolute top-[60%] right-[10%] w-[50%] h-[50%] rounded-full bg-pink-500/5 blur-3xl animate-blob" style="animation-delay: 4s"></div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════ -->
        <!-- NAVIGATION                                                      -->
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <nav class="sticky top-0 z-50 glass border-b border-gray-200/50 dark:border-gray-800/50">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <!-- Logo -->
                    <div class="flex items-center">
                        <Link href="/" class="flex items-center gap-3">
                            <ApplicationLogo variant="mark-with-text" class="h-9 w-auto" />
                        </Link>
                    </div>

                    <!-- Desktop Nav -->
                    <div class="hidden md:flex items-center gap-8">
                        <a href="#features" class="text-sm font-medium text-gray-600 hover:text-brand-600 dark:text-gray-300 dark:hover:text-brand-400 transition-colors">Features</a>
                        <a href="#how-it-works" class="text-sm font-medium text-gray-600 hover:text-brand-600 dark:text-gray-300 dark:hover:text-brand-400 transition-colors">How It Works</a>
                        <a href="#analytics" class="text-sm font-medium text-gray-600 hover:text-brand-600 dark:text-gray-300 dark:hover:text-brand-400 transition-colors">Analytics</a>
                        <a href="#pricing" class="text-sm font-medium text-gray-600 hover:text-brand-600 dark:text-gray-300 dark:hover:text-brand-400 transition-colors">Pricing</a>
                    </div>

                    <!-- Auth Buttons -->
                    <div class="hidden md:flex items-center gap-3">
                        <template v-if="canLogin">
                            <Link
                                v-if="$page.props.auth?.user"
                                :href="route('dashboard')"
                                class="inline-flex items-center justify-center rounded-lg border border-transparent bg-gradient-to-r from-brand-600 to-purple-600 px-5 py-2 text-sm font-semibold text-white shadow-md transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg hover:from-brand-500 hover:to-purple-500"
                            >
                                Dashboard
                            </Link>
                            <template v-else>
                                <Link
                                    :href="route('login')"
                                    class="text-sm font-medium text-gray-600 hover:text-brand-600 dark:text-gray-300 dark:hover:text-brand-400 transition-colors"
                                >
                                    Log in
                                </Link>
                                <Link
                                    v-if="canRegister"
                                    :href="route('register')"
                                    class="inline-flex items-center justify-center rounded-lg border border-transparent bg-gradient-to-r from-brand-600 to-purple-600 px-5 py-2 text-sm font-semibold text-white shadow-md transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg hover:from-brand-500 hover:to-purple-500"
                                >
                                    Get Started Free
                                </Link>
                            </template>
                        </template>
                    </div>

                    <!-- Mobile Menu Button -->
                    <button
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        class="md:hidden p-2 rounded-lg text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800 transition-colors"
                    >
                        <svg v-if="!mobileMenuOpen" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg v-else class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Mobile Menu -->
                <div v-if="mobileMenuOpen" class="md:hidden py-4 border-t border-gray-200/50 dark:border-gray-700/50 space-y-3">
                    <a href="#features" @click="mobileMenuOpen = false" class="block px-3 py-2 text-sm font-medium text-gray-600 hover:text-brand-600 dark:text-gray-300 dark:hover:text-brand-400">Features</a>
                    <a href="#how-it-works" @click="mobileMenuOpen = false" class="block px-3 py-2 text-sm font-medium text-gray-600 hover:text-brand-600 dark:text-gray-300 dark:hover:text-brand-400">How It Works</a>
                    <a href="#analytics" @click="mobileMenuOpen = false" class="block px-3 py-2 text-sm font-medium text-gray-600 hover:text-brand-600 dark:text-gray-300 dark:hover:text-brand-400">Analytics</a>
                    <a href="#pricing" @click="mobileMenuOpen = false" class="block px-3 py-2 text-sm font-medium text-gray-600 hover:text-brand-600 dark:text-gray-300 dark:hover:text-brand-400">Pricing</a>
                    <div class="pt-3 border-t border-gray-200/50 dark:border-gray-700/50 flex gap-3 px-3">
                        <Link v-if="!$page.props.auth?.user" :href="route('login')" class="flex-1 text-center py-2 text-sm font-medium text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg">Log in</Link>
                        <Link v-if="canRegister && !$page.props.auth?.user" :href="route('register')" class="flex-1 text-center py-2 text-sm font-semibold text-white bg-gradient-to-r from-brand-600 to-purple-600 rounded-lg">Get Started</Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- ═══════════════════════════════════════════════════════════════ -->
        <!-- HERO SECTION                                                    -->
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <section class="relative pt-16 pb-24 lg:pt-24 lg:pb-32">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                    <!-- Left: Copy -->
                    <div class="text-center lg:text-left animate-on-scroll opacity-0 translate-y-8 transition-all duration-700">
                        <div class="inline-flex items-center gap-2 rounded-full bg-brand-50 dark:bg-brand-900/30 px-4 py-1.5 text-sm font-medium text-brand-700 dark:text-brand-300 mb-6 border border-brand-200 dark:border-brand-800">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-brand-500"></span>
                            </span>
                            Free plan available — no credit card required
                        </div>

                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight leading-tight">
                            Shorten Links.<br />
                            <span class="bg-gradient-to-r from-brand-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">
                                Track Clicks.
                            </span><br />
                            Grow Faster.
                        </h1>

                        <p class="mt-6 text-lg text-gray-600 dark:text-gray-400 max-w-xl mx-auto lg:mx-0 leading-relaxed">
                            The most powerful link management platform for marketers, developers, and teams. 
                            Create short links, track analytics, build bio pages, and manage everything in one place.
                        </p>

                        <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                            <Link
                                v-if="canRegister && !$page.props.auth?.user"
                                :href="route('register')"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 to-purple-600 px-8 py-4 text-base font-bold text-white shadow-xl shadow-brand-500/25 transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-brand-500/30 hover:from-brand-500 hover:to-purple-500"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                Start Shortening Free
                            </Link>
                            <Link
                                v-else-if="$page.props.auth?.user"
                                :href="route('links.index')"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 to-purple-600 px-8 py-4 text-base font-bold text-white shadow-xl shadow-brand-500/25 transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-brand-500/30 hover:from-brand-500 hover:to-purple-500"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                Go to Dashboard
                            </Link>
                            <a
                                href="#features"
                                class="inline-flex items-center justify-center gap-2 rounded-xl border-2 border-gray-200 dark:border-gray-700 px-8 py-4 text-base font-semibold text-gray-700 dark:text-gray-300 transition-all duration-300 hover:border-brand-300 hover:text-brand-600 dark:hover:border-brand-700 dark:hover:text-brand-400"
                            >
                                Explore Features
                            </a>
                        </div>

                        <div class="mt-8 flex items-center justify-center lg:justify-start gap-6 text-sm text-gray-500 dark:text-gray-500">
                            <div class="flex items-center gap-2">
                                <svg class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Free forever plan
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                No credit card
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                API access
                            </div>
                        </div>
                    </div>

                    <!-- Right: Interactive Demo -->
                    <div class="animate-on-scroll opacity-0 translate-y-8 transition-all duration-700 delay-200">
                        <div class="relative">
                            <!-- Decorative elements -->
                            <div class="absolute -top-4 -right-4 w-24 h-24 bg-gradient-to-br from-brand-400/20 to-purple-400/20 rounded-full blur-2xl"></div>
                            <div class="absolute -bottom-4 -left-4 w-32 h-32 bg-gradient-to-br from-pink-400/20 to-brand-400/20 rounded-full blur-2xl"></div>

                            <!-- Demo Card -->
                            <div class="relative rounded-2xl bg-white dark:bg-gray-900 shadow-2xl shadow-gray-200/50 dark:shadow-none ring-1 ring-gray-200/60 dark:ring-gray-700/60 overflow-hidden">
                                <!-- Gradient top bar -->
                                <div class="h-1.5 bg-gradient-to-r from-brand-500 via-purple-500 to-pink-500"></div>

                                <div class="p-6 sm:p-8">
                                    <div class="flex items-center gap-3 mb-6">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-100 dark:bg-brand-900/40">
                                            <svg class="h-5 w-5 text-brand-600 dark:text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-gray-900 dark:text-white">Try it now</h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">No signup required for demo</p>
                                        </div>
                                    </div>

                                    <!-- Input -->
                                    <div class="relative">
                                        <div class="flex items-center gap-2 px-4 py-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 focus-within:border-brand-400 focus-within:ring-2 focus-within:ring-brand-500/20 transition-all">
                                            <svg class="h-5 w-5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                            </svg>
                                            <input
                                                v-model="demoUrl"
                                                type="url"
                                                placeholder="https://example.com/very/long/url?with=params"
                                                class="flex-1 bg-transparent text-sm text-gray-900 dark:text-white placeholder-gray-400 outline-none"
                                                @keydown.enter="handleDemoShorten"
                                            />
                                        </div>

                                        <button
                                            @click="handleDemoShorten"
                                            :disabled="demoProcessing || !demoUrl.trim()"
                                            class="mt-3 w-full flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 to-purple-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-brand-500/20 transition-all duration-300 hover:from-brand-500 hover:to-purple-500 hover:shadow-xl hover:shadow-brand-500/30 disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            <svg v-if="demoProcessing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                            {{ demoProcessing ? 'Shortening...' : 'Shorten URL' }}
                                        </button>
                                    </div>

                                    <!-- Result -->
                                    <div v-if="demoResult" class="mt-4 p-4 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 animate-scale-in">
                                        <div class="flex items-center gap-2 mb-2">
                                            <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span class="text-sm font-semibold text-green-800 dark:text-green-300">Link shortened!</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <a :href="demoResult.shortUrl" target="_blank" class="flex-1 text-sm font-mono text-brand-600 dark:text-brand-400 hover:underline truncate">
                                                {{ demoResult.shortUrl }}
                                            </a>
                                            <button
                                                id="demo-copy-btn"
                                                @click="copyDemoUrl"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                                            >
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                                <span>Copy</span>
                                            </button>
                                        </div>
                                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-500">
                                            Sign up free to customize aliases, track clicks, and manage all your links.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════════════════════════ -->
        <!-- TRUST / STATS BAR                                               -->
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <section id="stats-section" class="relative py-16 bg-gray-50/50 dark:bg-gray-900/30 border-y border-gray-200/50 dark:border-gray-800/50">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-8">
                    <div v-for="(stat, index) in stats" :key="stat.label" class="text-center animate-on-scroll opacity-0 translate-y-6 transition-all duration-700" :style="`transition-delay: ${index * 100}ms`">
                        <div class="text-3xl sm:text-4xl font-extrabold bg-gradient-to-r from-brand-600 to-purple-600 bg-clip-text text-transparent">
                            {{ stat.target >= 1000000 ? formatNumber(stat.value) : stat.value.toLocaleString() }}{{ stat.suffix }}
                        </div>
                        <div class="mt-1 text-sm font-medium text-gray-600 dark:text-gray-400">{{ stat.label }}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════════════════════════ -->
        <!-- FEATURES GRID                                                   -->
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <section id="features" class="py-24 lg:py-32">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Section Header -->
                <div class="text-center max-w-3xl mx-auto mb-16 animate-on-scroll opacity-0 translate-y-8 transition-all duration-700">
                    <div class="inline-flex items-center gap-2 rounded-full bg-purple-50 dark:bg-purple-900/20 px-4 py-1.5 text-sm font-medium text-purple-700 dark:text-purple-300 mb-4 border border-purple-200 dark:border-purple-800">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                        Powerful Features
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                        Everything you need to manage links
                    </h2>
                    <p class="mt-4 text-lg text-gray-600 dark:text-gray-400">
                        From simple shortening to advanced analytics and team collaboration, 
                        our platform scales with your needs.
                    </p>
                </div>

                <!-- Features Grid -->
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
                    <!-- Feature 1: Smart Shortening -->
                    <div class="group relative rounded-2xl bg-white dark:bg-gray-900 p-8 shadow-sm ring-1 ring-gray-200/60 dark:ring-gray-700/60 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-brand-500/5 hover:ring-brand-200 dark:hover:ring-brand-800 animate-on-scroll opacity-0 translate-y-8 transition-all duration-700">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-100 dark:bg-brand-900/30 text-brand-600 dark:text-brand-400 mb-5 transition-transform duration-300 group-hover:scale-110">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Smart Shortening</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            Create custom aliases, set expiration dates, add password protection, 
                            and build UTM parameters — all in one place.
                        </p>
                    </div>

                    <!-- Feature 2: Analytics -->
                    <div class="group relative rounded-2xl bg-white dark:bg-gray-900 p-8 shadow-sm ring-1 ring-gray-200/60 dark:ring-gray-700/60 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-purple-500/5 hover:ring-purple-200 dark:hover:ring-purple-800 animate-on-scroll opacity-0 translate-y-8 transition-all duration-700" style="transition-delay: 100ms">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 mb-5 transition-transform duration-300 group-hover:scale-110">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Powerful Analytics</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            Track every click with detailed insights on geography, referrers, 
                            devices, and time-based trends with beautiful charts.
                        </p>
                    </div>

                    <!-- Feature 3: Custom Domains -->
                    <div class="group relative rounded-2xl bg-white dark:bg-gray-900 p-8 shadow-sm ring-1 ring-gray-200/60 dark:ring-gray-700/60 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-pink-500/5 hover:ring-pink-200 dark:hover:ring-pink-800 animate-on-scroll opacity-0 translate-y-8 transition-all duration-700" style="transition-delay: 200ms">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-pink-100 dark:bg-pink-900/30 text-pink-600 dark:text-pink-400 mb-5 transition-transform duration-300 group-hover:scale-110">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Custom Domains</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            Use your own branded domain for short links. Build trust with 
                            your audience and reinforce your brand identity.
                        </p>
                    </div>

                    <!-- Feature 4: Bio Pages -->
                    <div class="group relative rounded-2xl bg-white dark:bg-gray-900 p-8 shadow-sm ring-1 ring-gray-200/60 dark:ring-gray-700/60 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-brand-500/5 hover:ring-brand-200 dark:hover:ring-brand-800 animate-on-scroll opacity-0 translate-y-8 transition-all duration-700" style="transition-delay: 300ms">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-100 dark:bg-brand-900/30 text-brand-600 dark:text-brand-400 mb-5 transition-transform duration-300 group-hover:scale-110">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Link-in-Bio Pages</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            Create beautiful bio pages for your social media profiles. 
                            One link, unlimited destinations. Fully customizable.
                        </p>
                    </div>

                    <!-- Feature 5: Team Workspaces -->
                    <div class="group relative rounded-2xl bg-white dark:bg-gray-900 p-8 shadow-sm ring-1 ring-gray-200/60 dark:ring-gray-700/60 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-purple-500/5 hover:ring-purple-200 dark:hover:ring-purple-800 animate-on-scroll opacity-0 translate-y-8 transition-all duration-700" style="transition-delay: 400ms">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 mb-5 transition-transform duration-300 group-hover:scale-110">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Team Workspaces</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            Collaborate with your team in shared workspaces. Manage permissions, 
                            invite members, and organize links together.
                        </p>
                    </div>

                    <!-- Feature 6: Developer API -->
                    <div class="group relative rounded-2xl bg-white dark:bg-gray-900 p-8 shadow-sm ring-1 ring-gray-200/60 dark:ring-gray-700/60 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-pink-500/5 hover:ring-pink-200 dark:hover:ring-pink-800 animate-on-scroll opacity-0 translate-y-8 transition-all duration-700" style="transition-delay: 500ms">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-pink-100 dark:bg-pink-900/30 text-pink-600 dark:text-pink-400 mb-5 transition-transform duration-300 group-hover:scale-110">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Developer API</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            Integrate link shortening into your apps with our REST API. 
                            Generate API keys, manage links programmatically, and automate workflows.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════════════════════════ -->
        <!-- HOW IT WORKS                                                    -->
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <section id="how-it-works" class="py-24 lg:py-32 bg-gray-50/50 dark:bg-gray-900/30">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16 animate-on-scroll opacity-0 translate-y-8 transition-all duration-700">
                    <div class="inline-flex items-center gap-2 rounded-full bg-brand-50 dark:bg-brand-900/20 px-4 py-1.5 text-sm font-medium text-brand-700 dark:text-brand-300 mb-4 border border-brand-200 dark:border-brand-800">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                        </svg>
                        Simple Process
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                        Get started in three easy steps
                    </h2>
                </div>

                <div class="grid md:grid-cols-3 gap-8 lg:gap-12">
                    <!-- Step 1 -->
                    <div class="relative text-center animate-on-scroll opacity-0 translate-y-8 transition-all duration-700">
                        <div class="flex items-center justify-center w-16 h-16 mx-auto rounded-2xl bg-gradient-to-br from-brand-500 to-brand-600 text-white text-2xl font-bold shadow-lg shadow-brand-500/25 mb-6">
                            1
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">Paste Your URL</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            Drop in any long URL you want to shorten. We support all web addresses, 
                            including those with query parameters and tracking codes.
                        </p>
                        <!-- Connector line (hidden on mobile) -->
                        <div class="hidden md:block absolute top-8 left-[60%] w-[80%] h-0.5 bg-gradient-to-r from-brand-300 to-purple-300 dark:from-brand-800 dark:to-purple-800"></div>
                    </div>

                    <!-- Step 2 -->
                    <div class="relative text-center animate-on-scroll opacity-0 translate-y-8 transition-all duration-700" style="transition-delay: 150ms">
                        <div class="flex items-center justify-center w-16 h-16 mx-auto rounded-2xl bg-gradient-to-br from-purple-500 to-purple-600 text-white text-2xl font-bold shadow-lg shadow-purple-500/25 mb-6">
                            2
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">Customize & Configure</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            Add a custom alias, set an expiration date, enable password protection, 
                            or append UTM parameters for tracking campaigns.
                        </p>
                        <!-- Connector line (hidden on mobile) -->
                        <div class="hidden md:block absolute top-8 left-[60%] w-[80%] h-0.5 bg-gradient-to-r from-purple-300 to-pink-300 dark:from-purple-800 dark:to-pink-800"></div>
                    </div>

                    <!-- Step 3 -->
                    <div class="relative text-center animate-on-scroll opacity-0 translate-y-8 transition-all duration-700" style="transition-delay: 300ms">
                        <div class="flex items-center justify-center w-16 h-16 mx-auto rounded-2xl bg-gradient-to-br from-pink-500 to-pink-600 text-white text-2xl font-bold shadow-lg shadow-pink-500/25 mb-6">
                            3
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">Share & Analyze</h3>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            Copy your short link and share it anywhere. Watch real-time analytics 
                            roll in and optimize your campaigns for better results.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════════════════════════ -->
        <!-- ANALYTICS PREVIEW                                               -->
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <section id="analytics" class="py-24 lg:py-32">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                    <!-- Left: Content -->
                    <div class="animate-on-scroll opacity-0 translate-y-8 transition-all duration-700">
                        <div class="inline-flex items-center gap-2 rounded-full bg-pink-50 dark:bg-pink-900/20 px-4 py-1.5 text-sm font-medium text-pink-700 dark:text-pink-300 mb-4 border border-pink-200 dark:border-pink-800">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            Deep Insights
                        </div>
                        <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white mb-4">
                            Understand your audience
                        </h2>
                        <p class="text-lg text-gray-600 dark:text-gray-400 leading-relaxed mb-8">
                            Every click tells a story. Our analytics dashboard gives you a complete picture 
                            of who is clicking your links, where they are coming from, and what devices they use.
                        </p>

                        <div class="space-y-4">
                            <div class="flex items-start gap-4">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-100 dark:bg-brand-900/30 text-brand-600 dark:text-brand-400 flex-shrink-0">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">Geographic Data</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">See which countries and cities your clicks come from.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-4">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 flex-shrink-0">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">Device & Browser</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Know what devices, operating systems, and browsers your visitors use.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-4">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-pink-100 dark:bg-pink-900/30 text-pink-600 dark:text-pink-400 flex-shrink-0">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">Time-Based Trends</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">View click volume over time with interactive charts and date range filters.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Visual Placeholder -->
                    <div class="animate-on-scroll opacity-0 translate-y-8 transition-all duration-700 delay-200">
                        <div class="relative rounded-2xl bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-900 p-8 lg:p-12 ring-1 ring-gray-200 dark:ring-gray-700">
                            <!-- Description for image placeholder -->
                            <div class="text-center">
                                <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-white dark:bg-gray-800 shadow-lg mb-6">
                                    <svg class="h-10 w-10 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Analytics Dashboard Screenshot</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 max-w-sm mx-auto">
                                    Add a screenshot of the actual analytics dashboard here showing 
                                    the click volume chart, stats cards, and link performance table.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════════════════════════ -->
        <!-- PRICING CTA                                                     -->
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <section id="pricing" class="py-24 lg:py-32 bg-gray-50/50 dark:bg-gray-900/30">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
                <div class="animate-on-scroll opacity-0 translate-y-8 transition-all duration-700">
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-gray-900 dark:text-white mb-6">
                        Ready to start shortening?
                    </h2>
                    <p class="text-lg text-gray-600 dark:text-gray-400 mb-10 max-w-2xl mx-auto leading-relaxed">
                        Join thousands of marketers, developers, and teams who trust our platform 
                        to manage their links. Start free and scale as you grow.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <Link
                            v-if="canRegister && !$page.props.auth?.user"
                            :href="route('register')"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 to-purple-600 px-8 py-4 text-base font-bold text-white shadow-xl shadow-brand-500/25 transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-brand-500/30 hover:from-brand-500 hover:to-purple-500"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            Get Started Free
                        </Link>
                        <Link
                            v-else-if="$page.props.auth?.user"
                            :href="route('links.index')"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 to-purple-600 px-8 py-4 text-base font-bold text-white shadow-xl shadow-brand-500/25 transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-brand-500/30 hover:from-brand-500 hover:to-purple-500"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            Go to Dashboard
                        </Link>
                        <a
                            href="#features"
                            class="inline-flex items-center justify-center gap-2 rounded-xl border-2 border-gray-200 dark:border-gray-700 px-8 py-4 text-base font-semibold text-gray-700 dark:text-gray-300 transition-all duration-300 hover:border-brand-300 hover:text-brand-600 dark:hover:border-brand-700 dark:hover:text-brand-400"
                        >
                            Explore Features
                        </a>
                    </div>

                    <div class="mt-8 flex items-center justify-center gap-6 text-sm text-gray-500 dark:text-gray-500">
                        <div class="flex items-center gap-2">
                            <svg class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Free forever plan
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            No credit card required
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Cancel anytime
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════════════════════════ -->
        <!-- FOOTER                                                          -->
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <footer class="border-t border-gray-200/50 dark:border-gray-800/50 bg-white dark:bg-gray-950">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
                <div class="grid md:grid-cols-4 gap-8 mb-8">
                    <!-- Brand -->
                    <div class="md:col-span-2">
                        <div class="flex items-center gap-2.5 mb-4">
                            <ApplicationLogo variant="wordmark" class="h-8 w-auto" />
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 max-w-sm leading-relaxed">
                            The modern link management platform for individuals and teams. 
                            Shorten, track, and optimize your links with powerful analytics.
                        </p>
                    </div>

                    <!-- Product -->
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-4">Product</h4>
                        <ul class="space-y-2">
                            <li>
                                <a href="#features" class="text-sm text-gray-600 dark:text-gray-400 hover:text-brand-600 dark:hover:text-brand-400 transition-colors">Features</a>
                            </li>
                            <li>
                                <a href="#pricing" class="text-sm text-gray-600 dark:text-gray-400 hover:text-brand-600 dark:hover:text-brand-400 transition-colors">Pricing</a>
                            </li>
                            <li>
                                <Link v-if="canLogin && !$page.props.auth?.user" :href="route('register')" class="text-sm text-gray-600 dark:text-gray-400 hover:text-brand-600 dark:hover:text-brand-400 transition-colors">Sign Up</Link>
                            </li>
                        </ul>
                    </div>

                    <!-- Resources -->
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-4">Resources</h4>
                        <ul class="space-y-2">
                            <li>
                                <a href="#how-it-works" class="text-sm text-gray-600 dark:text-gray-400 hover:text-brand-600 dark:hover:text-brand-400 transition-colors">How It Works</a>
                            </li>
                            <li>
                                <a href="#analytics" class="text-sm text-gray-600 dark:text-gray-400 hover:text-brand-600 dark:hover:text-brand-400 transition-colors">Analytics</a>
                            </li>
                            <li>
                                <Link v-if="canLogin && !$page.props.auth?.user" :href="route('login')" class="text-sm text-gray-600 dark:text-gray-400 hover:text-brand-600 dark:hover:text-brand-400 transition-colors">Log In</Link>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="border-t border-gray-200/50 dark:border-gray-800/50 pt-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-sm text-gray-500 dark:text-gray-500">
                        &copy; {{ new Date().getFullYear() }} Elido. All rights reserved.
                    </p>
                    <div class="flex items-center gap-4">
                        <span class="text-xs text-gray-400 dark:text-gray-600">Built with Laravel & Vue.js</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
/* Scroll-triggered animation utility */
.animate-on-scroll {
    opacity: 0;
    transform: translateY(30px);
    transition: opacity 0.7s ease-out, transform 0.7s ease-out;
}

.animate-on-scroll.animate-visible {
    opacity: 1;
    transform: translateY(0);
}

/* Scale-in animation for demo result */
@keyframes scaleIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.animate-scale-in {
    animation: scaleIn 0.3s ease-out forwards;
}
</style>
