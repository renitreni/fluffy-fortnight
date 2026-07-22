<script setup>
/**
 * IconButton Component
 *
 * A compact, square button designed to hold a single icon. Ideal for
 * toolbar actions, table row actions (edit, delete, copy), and inline controls.
 *
 * @prop {String}  variant  - Visual style: ghost | solid | outline (default: 'ghost')
 * @prop {String}  size     - Size: xs | sm | md | lg (default: 'md')
 * @prop {String}  type     - HTML button type attribute (default: 'button')
 * @prop {Boolean} disabled - Disables the button (default: false)
 * @prop {String}  label    - Accessible aria-label for screen readers (required)
 */
defineProps({
    variant: {
        type: String,
        default: 'ghost',
        validator: (v) => ['ghost', 'solid', 'outline'].includes(v),
    },
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['xs', 'sm', 'md', 'lg'].includes(v),
    },
    type: {
        type: String,
        default: 'button',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    label: {
        type: String,
        required: true,
    },
});

const sizeClasses = {
    xs: 'h-6 w-6',
    sm: 'h-8 w-8',
    md: 'h-9 w-9',
    lg: 'h-10 w-10',
};

const iconSizeClasses = {
    xs: 'h-3.5 w-3.5',
    sm: 'h-4 w-4',
    md: 'h-5 w-5',
    lg: 'h-5 w-5',
};

const variantClasses = {
    ghost:   'text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200',
    solid:   'bg-brand-600 text-white shadow hover:bg-brand-500 focus:ring-brand-500',
    outline: 'border border-gray-300 text-gray-600 hover:border-gray-400 hover:bg-gray-50 hover:text-gray-800 dark:border-gray-600 dark:text-gray-400 dark:hover:border-gray-500 dark:hover:bg-gray-800 dark:hover:text-gray-200',
};
</script>

<template>
    <button
        :type="type"
        :disabled="disabled"
        :aria-label="label"
        :class="[
            'inline-flex flex-shrink-0 items-center justify-center rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-40 dark:focus:ring-offset-gray-900',
            sizeClasses[size],
            variantClasses[variant],
        ]"
    >
        <!-- Wrap slotted icon in a size-controlled span -->
        <span :class="iconSizeClasses[size]" class="flex items-center justify-center">
            <slot />
        </span>
    </button>
</template>
