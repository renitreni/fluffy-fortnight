<template>
    <button
        :id="id"
        type="button"
        :aria-label="copied ? 'Copied!' : 'Copy to clipboard'"
        class="copy-btn"
        :class="[sizeClass, variantClass, { copied: copied }]"
        @click="copy"
    >
        <span class="copy-btn__icon" aria-hidden="true">
            <!-- Clipboard icon -->
            <svg
                v-if="!copied"
                xmlns="http://www.w3.org/2000/svg"
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
            <!-- Check icon (copied state) -->
            <svg
                v-else
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2.5"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <polyline points="20 6 9 17 4 12" />
            </svg>
        </span>
        <span v-if="showLabel" class="copy-btn__label">
            {{ copied ? 'Copied!' : label }}
        </span>
    </button>
</template>

<script setup>
import { ref, computed } from 'vue';

/**
 * CopyButton — A reusable clipboard copy button.
 *
 * Accepts a `value` prop (the text to copy) and shows animated
 * "Copied!" feedback for 2 seconds after a successful copy.
 *
 * Variants: 'ghost' (default), 'solid', 'outline'
 * Sizes:    'sm', 'md' (default), 'lg'
 */
const props = defineProps({
    /** The text content to write to the clipboard. */
    value: {
        type: String,
        required: true,
    },
    /** Button label shown alongside the icon (only when showLabel is true). */
    label: {
        type: String,
        default: 'Copy',
    },
    /** Whether to show the text label next to the icon. */
    showLabel: {
        type: Boolean,
        default: false,
    },
    /** Visual variant of the button. */
    variant: {
        type: String,
        default: 'ghost',
        validator: (v) => ['ghost', 'solid', 'outline'].includes(v),
    },
    /** Size of the button. */
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['sm', 'md', 'lg'].includes(v),
    },
    /** Unique HTML id for the button element. */
    id: {
        type: String,
        default: undefined,
    },
});

const emit = defineEmits(['copied']);

const copied = ref(false);
let resetTimer = null;

const sizeClass = computed(() => ({
    'copy-btn--sm': props.size === 'sm',
    'copy-btn--md': props.size === 'md',
    'copy-btn--lg': props.size === 'lg',
}));

const variantClass = computed(() => ({
    'copy-btn--ghost': props.variant === 'ghost',
    'copy-btn--solid': props.variant === 'solid',
    'copy-btn--outline': props.variant === 'outline',
}));

async function copy() {
    try {
        await navigator.clipboard.writeText(props.value);
        copied.value = true;
        emit('copied', props.value);

        clearTimeout(resetTimer);
        resetTimer = setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch {
        // Fallback for browsers that don't support navigator.clipboard
        const textarea = document.createElement('textarea');
        textarea.value = props.value;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        copied.value = true;
        clearTimeout(resetTimer);
        resetTimer = setTimeout(() => {
            copied.value = false;
        }, 2000);
    }
}
</script>

<style scoped>
.copy-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    border-radius: 0.5rem;
    font-weight: 500;
    transition: all 0.2s ease;
    cursor: pointer;
    border: none;
    outline: none;
    flex-shrink: 0;
}

.copy-btn:focus-visible {
    outline: 2px solid #6366f1;
    outline-offset: 2px;
}

/* Sizes */
.copy-btn--sm {
    padding: 0.25rem;
    font-size: 0.75rem;
}
.copy-btn--sm .copy-btn__icon svg {
    width: 14px;
    height: 14px;
}

.copy-btn--md {
    padding: 0.375rem;
    font-size: 0.875rem;
}
.copy-btn--md .copy-btn__icon svg {
    width: 16px;
    height: 16px;
}

.copy-btn--lg {
    padding: 0.5rem;
    font-size: 1rem;
}
.copy-btn--lg .copy-btn__icon svg {
    width: 18px;
    height: 18px;
}

/* Ghost variant */
.copy-btn--ghost {
    background: transparent;
    color: #6b7280;
}
.copy-btn--ghost:hover {
    background: rgba(99, 102, 241, 0.1);
    color: #6366f1;
}
.copy-btn--ghost.copied {
    color: #10b981;
}

/* Solid variant */
.copy-btn--solid {
    background: #6366f1;
    color: white;
}
.copy-btn--solid:hover {
    background: #4f46e5;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.35);
}
.copy-btn--solid.copied {
    background: #10b981;
}

/* Outline variant */
.copy-btn--outline {
    background: transparent;
    color: #6366f1;
    border: 1.5px solid #6366f1;
}
.copy-btn--outline:hover {
    background: rgba(99, 102, 241, 0.08);
}
.copy-btn--outline.copied {
    color: #10b981;
    border-color: #10b981;
}

/* Copied state transitions */
.copy-btn__icon {
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
