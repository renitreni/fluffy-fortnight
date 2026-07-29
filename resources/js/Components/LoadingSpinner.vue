<script setup>
/**
 * LoadingSpinner Component
 *
 * A smooth, animated SVG spinner for indicating async/loading states.
 * Supports multiple sizes and color variants to match the design system.
 *
 * @prop {String} size    - Size of the spinner: xs | sm | md | lg | xl (default: 'md')
 * @prop {String} variant - Color variant: brand | white | gray (default: 'brand')
 * @prop {String} label   - Accessible sr-only label (default: 'Loading…')
 */
defineProps({
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(v),
    },
    variant: {
        type: String,
        default: 'brand',
        validator: (v) => ['brand', 'white', 'gray'].includes(v),
    },
    label: {
        type: String,
        default: 'Loading…',
    },
});

const sizeClasses = {
    xs: 'h-3 w-3',
    sm: 'h-4 w-4',
    md: 'h-6 w-6',
    lg: 'h-8 w-8',
    xl: 'h-12 w-12',
};

const trackClasses = {
    brand: 'text-brand-200 dark:text-brand-900',
    white: 'text-white/30',
    gray: 'text-gray-200 dark:text-gray-700',
};

const spinClasses = {
    brand: 'text-brand-600 dark:text-brand-400',
    white: 'text-white',
    gray: 'text-gray-500 dark:text-gray-400',
};
</script>

<template>
    <span role="status" class="inline-flex items-center">
        <svg
            :class="[sizeClasses[size], 'animate-spin']"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <!-- Track ring -->
            <circle
                :class="trackClasses[variant]"
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
            />
            <!-- Spinning arc -->
            <path
                :class="spinClasses[variant]"
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            />
        </svg>
        <span class="sr-only">{{ label }}</span>
    </span>
</template>
