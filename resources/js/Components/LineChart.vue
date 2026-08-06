<script setup>
import { computed } from 'vue';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';
import { Line } from 'vue-chartjs';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler,
);

const props = defineProps({
    chartData: {
        type: Object,
        required: true,
    },
    height: {
        type: Number,
        default: 300,
    },
});

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        mode: 'index',
        intersect: false,
    },
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            backgroundColor: 'rgba(15, 23, 42, 0.9)',
            titleColor: '#fff',
            bodyColor: '#cbd5e1',
            padding: 12,
            cornerRadius: 8,
            displayColors: false,
            callbacks: {
                label(context) {
                    let label = context.dataset.label || '';
                    if (label) {
                        label += ': ';
                    }
                    if (context.parsed.y !== null) {
                        label += context.parsed.y;
                    }
                    return label;
                },
            },
        },
    },
    scales: {
        x: {
            grid: {
                display: false,
                drawBorder: false,
            },
            ticks: {
                color: '#94a3b8',
                maxTicksLimit: 7,
            },
        },
        y: {
            beginAtZero: true,
            grid: {
                color: 'rgba(203, 213, 225, 0.2)',
                drawBorder: false,
                borderDash: [5, 5],
            },
            ticks: {
                color: '#94a3b8',
                padding: 10,
                precision: 0,
            },
        },
    },
    elements: {
        line: {
            tension: 0.4, // smooth curves
        },
        point: {
            radius: 0,
            hitRadius: 10,
            hoverRadius: 4,
        },
    },
}));
</script>

<template>
    <div :style="{ height: `${height}px` }">
        <Line :data="chartData" :options="chartOptions" />
    </div>
</template>
