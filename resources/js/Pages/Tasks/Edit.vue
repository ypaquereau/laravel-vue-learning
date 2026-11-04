<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TagInput from '@/Components/TagInput.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    task: {
        type: Object,
        required: true,
    },
    availableTags: { type: Array, default: () => [] },
});

// useForm seeded from a prop is the Inertia equivalent of a React
// controlled-form component initialised from `defaultValues` — the data
// Laravel put in the page already IS the initial form state, no extra
// fetch to "load the record for editing".
const form = useForm({
    title: props.task.title,
    description: props.task.description,
    completed: props.task.completed,
    tags: (props.task.tags ?? []).map((t) => t.name),
});

function submit() {
    form.put(route('tasks.update', props.task.id));
}
</script>

<template>
    <Head title="Edit Task" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Edit Task
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <InputLabel for="title" value="Title" />
                            <TextInput
                                id="title"
                                v-model="form.title"
                                type="text"
                                class="mt-1 block w-full"
                                autofocus
                            />
                            <InputError class="mt-2" :message="form.errors.title" />
                        </div>

                        <div>
                            <InputLabel for="description" value="Description" />
                            <textarea
                                id="description"
                                v-model="form.description"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                rows="4"
                            />
                            <InputError class="mt-2" :message="form.errors.description" />
                        </div>

                        <div>
                            <InputLabel for="tags" value="Tags" />
                            <TagInput id="tags" v-model="form.tags" :suggestions="availableTags" />
                            <InputError class="mt-2" :message="form.errors.tags" />
                        </div>

                        <div class="flex items-center gap-2">
                            <!--
                                v-model on a custom component is sugar for
                                :checked="form.completed"
                                @update:checked="form.completed = $event"
                                — the emit declared in Checkbox.vue is what
                                makes this two-way binding possible, the
                                Vue equivalent of a controlled React
                                checkbox's `checked` + `onChange` pair.
                            -->
                            <Checkbox v-model:checked="form.completed" />
                            <InputLabel value="Completed" />
                        </div>

                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="form.processing">
                                Save Changes
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
