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
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    tasks: {
        type: Array,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ tag: null }),
    },
    availableTags: { type: Array, default: () => [] },
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
                    <div
                        v-if="availableTags.length"
                        class="flex flex-wrap items-center gap-2 border-b border-gray-100 p-4"
                    >
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Filter</span>
                        <Link
                            v-for="tag in availableTags"
                            :key="tag.id"
                            :href="route('tasks.index', { tag: tag.slug })"
                            class="rounded-md px-2 py-1 text-xs font-medium"
                            :class="filters.tag === tag.slug
                                ? 'bg-gray-800 text-white'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        >
                            {{ tag.name }}
                        </Link>
                        <Link
                            v-if="filters.tag"
                            :href="route('tasks.index')"
                            class="text-xs text-gray-500 underline hover:text-gray-800"
                        >
                            Clear
                        </Link>
                    </div>

                    <ul
                        v-if="tasks.length"
                        class="divide-y divide-gray-200"
                    >
                        <li
                            v-for="task in tasks"
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
                                <div v-if="task.tags?.length" class="mt-1 flex flex-wrap gap-1">
                                    <Link
                                        v-for="tag in task.tags"
                                        :key="tag.id"
                                        :href="route('tasks.index', { tag: tag.slug })"
                                        class="rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 hover:bg-gray-200"
                                    >
                                        {{ tag.name }}
                                    </Link>
                                </div>
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

                    <p v-else class="p-6 text-gray-500">
                        No tasks yet — create your first one.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
