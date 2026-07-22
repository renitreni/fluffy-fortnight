<script setup>
/**
 * Badge Component
 *
 * A versatile status/label badge supporting multiple color variants and sizes.
 *
 * @prop {String} variant - Color variant: default | primary | success | warning | danger | info (default: 'default')
 * @prop {String} size    - Size: sm | md | lg (default: 'md')
 * @prop {Boolean} dot    - Show a pulsing dot indicator before the label (default: false)
 */
defineProps({
    variant: {
        type: String,
        default: 'default',
        validator: (v) => ['default', 'primary', 'success', 'warning', 'danger', 'info'].includes(v),
    },
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['sm', 'md', 'lg'].includes(v),
    },
    dot: {
        type: Boolean,
        default: false,
    },
});

const variantClasses = {
    default: 'bg-gray-100 text-gray-700 ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700',
    primary: 'bg-brand-100 text-brand-700 ring-brand-200 dark:bg-brand-900/40 dark:text-brand-300 dark:ring-brand-700/50',
    success: 'bg-emerald-100 text-emerald-700 ring-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300 dark:ring-emerald-700/50',
    warning: 'bg-amber-100 text-amber-700 ring-amber-200 dark:bg-amber-900/40 dark:text-amber-300 dark:ring-amber-700/50',
    danger:  'bg-red-100 text-red-700 ring-red-200 dark:bg-red-900/40 dark:text-red-300 dark:ring-red-700/50',
    info:    'bg-sky-100 text-sky-700 ring-sky-200 dark:bg-sky-900/40 dark:text-sky-300 dark:ring-sky-700/50',
};

const dotClasses = {
    default: 'bg-gray-500',
    primary: 'bg-brand-500',
    success: 'bg-emerald-500',
    warning: 'bg-amber-500',
    danger:  'bg-red-500',
    info:    'bg-sky-500',
};

const sizeClasses = {
    sm: 'px-1.5 py-0.5 text-xs',
    md: 'px-2.5 py-1 text-xs',
    lg: 'px-3 py-1.5 text-sm',
};
</script>

<template>
    <span
        :class="[
            'inline-flex items-center gap-x-1.5 rounded-full font-medium ring-1 ring-inset',
            variantClasses[variant],
            sizeClasses[size],
        ]"
    >
        <!-- Pulsing dot indicator -->
        <span v-if="dot" class="relative flex h-2 w-2 flex-shrink-0">
            <span
                :class="['absolute inline-flex h-full w-full animate-ping rounded-full opacity-75', dotClasses[variant]]"
            ></span>
            <span
                :class="['relative inline-flex h-2 w-2 rounded-full', dotClasses[variant]]"
            ></span>
        </span>

        <slot />
    </span>
</template>
