<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Bar, Doughnut } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
    ArcElement,
} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement);

const props = defineProps({
    link: Object,
    stats: Object,
});

// Helper for generic Bar Chart
const createBarChartData = (dataArray, labelKey, valueKey, color = '#6366f1') => {
    return {
        labels: dataArray.map(item => item[labelKey] || 'Unknown'),
        datasets: [
            {
                label: 'Clicks',
                backgroundColor: color,
                data: dataArray.map(item => item[valueKey]),
            }
        ]
    };
};

const createDoughnutData = (dataArray, labelKey, valueKey) => {
    const colors = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316'];
    return {
        labels: dataArray.map(item => item[labelKey] || 'Unknown'),
        datasets: [
            {
                backgroundColor: colors.slice(0, dataArray.length),
                data: dataArray.map(item => item[valueKey]),
            }
        ]
    };
};

const countriesChartData = computed(() => createBarChartData(props.stats.countries, 'country', 'total', '#10b981'));
const referrersChartData = computed(() => createBarChartData(props.stats.referrers, 'domain', 'total', '#f59e0b'));
const devicesChartData = computed(() => createDoughnutData(props.stats.devices, 'device_type', 'total'));
const browsersChartData = computed(() => createDoughnutData(props.stats.browsers, 'browser', 'total'));
const osChartData = computed(() => createDoughnutData(props.stats.os, 'os', 'total'));

const barOptions = {
    responsive: true,
    maintainAspectRatio: false,
    indexAxis: 'y', // Horizontal bar chart
    plugins: {
        legend: { display: false }
    }
};

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
};

</script>

<template>
    <Head title="Link Analytics" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold leading-tight text-gray-900 dark:text-white">
                        Analytics: {{ link.title }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        <a :href="link.short_url" target="_blank" class="text-brand-600 hover:underline">{{ link.short_url }}</a>
                        <span class="mx-2">&bull;</span>
                        <span title="Original URL">{{ link.original_url.length > 50 ? link.original_url.substring(0, 50) + '...' : link.original_url }}</span>
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total Clicks</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ link.click_count }}</p>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                
                <!-- Main Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Countries -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Clicks by Country</h3>
                        <div v-if="stats.countries.length > 0" class="h-64">
                            <Bar :data="countriesChartData" :options="barOptions" />
                        </div>
                        <div v-else class="text-gray-500 text-center py-10">No country data available.</div>
                    </div>

                    <!-- Referrers -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Top Referrers</h3>
                        <div v-if="stats.referrers.length > 0" class="h-64">
                            <Bar :data="referrersChartData" :options="barOptions" />
                        </div>
                        <div v-else class="text-gray-500 text-center py-10">No referrer data available.</div>
                    </div>

                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <!-- Devices -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Device Types</h3>
                        <div v-if="stats.devices.length > 0" class="h-48">
                            <Doughnut :data="devicesChartData" :options="doughnutOptions" />
                        </div>
                        <div v-else class="text-gray-500 text-center py-10">No device data available.</div>
                    </div>

                    <!-- Browsers -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Browsers</h3>
                        <div v-if="stats.browsers.length > 0" class="h-48">
                            <Doughnut :data="browsersChartData" :options="doughnutOptions" />
                        </div>
                        <div v-else class="text-gray-500 text-center py-10">No browser data available.</div>
                    </div>

                    <!-- OS -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Operating Systems</h3>
                        <div v-if="stats.os.length > 0" class="h-48">
                            <Doughnut :data="osChartData" :options="doughnutOptions" />
                        </div>
                        <div v-else class="text-gray-500 text-center py-10">No OS data available.</div>
                    </div>

                </div>

                <div class="flex justify-start">
                    <Link :href="route('dashboard')" class="text-brand-600 hover:text-brand-700 font-medium">
                        &larr; Back to Dashboard
                    </Link>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
