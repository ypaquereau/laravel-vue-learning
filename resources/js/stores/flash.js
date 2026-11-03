import { defineStore } from 'pinia';

/**
 * A Pinia store is a standalone piece of global state plus the functions
 * that mutate it — think a single Redux slice, or a Context + useReducer
 * pair, but without the provider wiring or action-type boilerplate: any
 * component calls useFlashStore() and gets the same live instance.
 *
 * This one holds the toast message that comes from Laravel's session flash
 * data (shared via HandleInertiaRequests::share -> 'flash' prop). See
 * Layouts/AuthenticatedLayout.vue for where it's read, and LEARNING.md
 * "Pinia vs Redux/Context" for the fuller comparison.
 */
export const useFlashStore = defineStore('flash', {
    state: () => ({
        message: null,
    }),
    actions: {
        show(message) {
            this.message = message;
        },
        clear() {
            this.message = null;
        },
    },
});
