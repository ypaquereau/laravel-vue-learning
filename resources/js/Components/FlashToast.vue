<script setup>
/**
 * Composition API + <script setup> is Vue's analogue of a React functional
 * component with hooks: `ref`/`computed` replace `useState`/`useMemo`, and
 * there's no return statement — every top-level binding here is
 * automatically exposed to the template. See LEARNING.md "Composition API
 * vs hooks".
 *
 * usePage() is Inertia's useContext-equivalent: it reads the props Laravel
 * shared for the CURRENT page without them being passed down explicitly.
 */
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import { useFlashStore } from '@/stores/flash';

const page = usePage();
const flash = useFlashStore();

// Laravel flashes 'success' into the session for exactly one request/response
// cycle. This watcher moves it into the Pinia store so the toast can animate
// out on a timer instead of disappearing the instant Inertia swaps pages.
watch(
    () => page.props.flash.success,
    (message) => {
        if (message) {
            flash.show(message);
            setTimeout(() => flash.clear(), 3000);
        }
    },
    { immediate: true },
);
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0 translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="flash.message"
            class="fixed bottom-4 right-4 z-50 rounded-md bg-green-600 px-4 py-3 text-sm font-medium text-white shadow-lg"
        >
            {{ flash.message }}
        </div>
    </Transition>
</template>
