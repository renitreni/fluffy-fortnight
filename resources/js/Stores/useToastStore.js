import { defineStore } from 'pinia';

export const useToastStore = defineStore('toast', {
    state: () => ({
        toasts: [],
    }),
    actions: {
        addToast(toast) {
            const id = Date.now() + Math.random().toString(36).substring(2, 9);
            const newToast = { id, ...toast };
            this.toasts.push(newToast);

            // Auto-remove after 5 seconds by default
            const duration = toast.duration || 5000;
            if (duration > 0) {
                setTimeout(() => {
                    this.removeToast(id);
                }, duration);
            }
            return id;
        },
        removeToast(id) {
            const index = this.toasts.findIndex((t) => t.id === id);
            if (index !== -1) {
                this.toasts.splice(index, 1);
            }
        },
        success(message, duration = 5000) {
            this.addToast({ message, type: 'success', duration });
        },
        error(message, duration = 5000) {
            this.addToast({ message, type: 'error', duration });
        },
        info(message, duration = 5000) {
            this.addToast({ message, type: 'info', duration });
        },
        warning(message, duration = 5000) {
            this.addToast({ message, type: 'warning', duration });
        },
    },
});
