<script setup>
/**
 * `defineProps` declares the component's public API — Vue's equivalent of
 * a React component's function parameters / a TypeScript props interface.
 * Inertia fills these from TaskController@index's `Inertia::render(...,
 * ['tasks' => $tasks])`: there is no client-side fetch, no loading state,
 * no useEffect — the server already rendered the data into the page props
 * before Vue even mounts. See LEARNING.md "props/emits vs props/callbacks".
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    tasks: {
        type: Array,
        required: true,
    },
});

const searchQuery = ref('');

const filteredTasks = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) {
        return props.tasks;
    }

    return props.tasks.filter((task) =>
        String(task.title ?? '')
            .toLowerCase()
            .includes(q),
    );
});

function destroy(task) {
    if (confirm(`Delete "${task.title}"?`)) {
        // router.delete is Inertia's client-side navigation helper — it
        // issues the DELETE and then follows the controller's redirect,
        // re-rendering Index with fresh props. No manual refetch needed.
        router.delete(route('tasks.destroy', task.id));
    }
}
</script>

<template>
    <Head title="Tasks" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Tasks
                </h2>
                <Link
                    :href="route('tasks.create')"
                    class="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-gray-700"
                >
                    New Task
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200 p-4">
                        <InputLabel for="task-search" value="Search" />
                        <TextInput
                            id="task-search"
                            v-model="searchQuery"
                            type="search"
                            class="mt-1 block w-full"
                            placeholder="Filter by title…"
                            autocomplete="off"
                        />
                    </div>

                    <ul
                        v-if="filteredTasks.length"
                        class="divide-y divide-gray-200"
                    >
                        <li
                            v-for="task in filteredTasks"
                            :key="task.id"
                            class="flex items-center justify-between gap-4 p-4"
                        >
                            <div class="min-w-0">
                                <p
                                    class="truncate font-medium text-gray-900"
                                    :class="{ 'line-through text-gray-400': task.completed }"
                                >
                                    {{ task.title }}
                                </p>
                                <p
                                    v-if="task.description"
                                    class="truncate text-sm text-gray-500"
                                >
                                    {{ task.description }}
                                </p>
                            </div>

                            <div class="flex shrink-0 items-center gap-3 text-sm">
                                <span
                                    class="rounded-full px-2 py-1 text-xs font-semibold"
                                    :class="task.completed
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-yellow-100 text-yellow-800'"
                                >
                                    {{ task.completed ? 'Done' : 'Pending' }}
                                </span>
                                <Link
                                    :href="route('tasks.edit', task.id)"
                                    class="text-gray-600 hover:text-gray-900"
                                >
                                    Edit
                                </Link>
                                <button
                                    type="button"
                                    class="text-red-600 hover:text-red-900"
                                    @click="destroy(task)"
                                >
                                    Delete
                                </button>
                            </div>
                        </li>
                    </ul>

                    <p
                        v-else-if="tasks.length"
                        class="p-6 text-gray-500"
                    >
                        No tasks match your search.
                    </p>

                    <p
                        v-else
                        class="p-6 text-gray-500"
                    >
                        No tasks yet — create your first one.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
