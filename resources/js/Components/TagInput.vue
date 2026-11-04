<script setup>
import { ref } from 'vue';

const props = defineProps({
    modelValue: { type: Array, required: true },
    suggestions: { type: Array, default: () => [] },
    id: { type: String, default: 'tags' },
});

const emit = defineEmits(['update:modelValue']);

const draft = ref('');

function normalizedExists(name) {
    const lower = name.toLowerCase();
    return props.modelValue.some((t) => t.toLowerCase() === lower);
}

function addFromDraft() {
    const parts = draft.value.split(',').map((p) => p.trim()).filter(Boolean);
    if (!parts.length) return;

    const next = [...props.modelValue];
    for (const part of parts) {
        if (part.length > 30) continue;
        if (next.length >= 10) break;
        if (!normalizedExists(part) && !next.some((t) => t.toLowerCase() === part.toLowerCase())) {
            next.push(part);
        }
    }
    emit('update:modelValue', next);
    draft.value = '';
}

function removeAt(index) {
    emit(
        'update:modelValue',
        props.modelValue.filter((_, i) => i !== index),
    );
}

function onKeydown(event) {
    if (event.key === 'Enter' || event.key === ',') {
        event.preventDefault();
        addFromDraft();
    }
    if (event.key === 'Backspace' && draft.value === '' && props.modelValue.length) {
        removeAt(props.modelValue.length - 1);
    }
}
</script>

<template>
    <div>
        <div class="flex flex-wrap gap-2 mb-2">
            <span
                v-for="(tag, index) in modelValue"
                :key="tag + index"
                class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700"
            >
                {{ tag }}
                <button type="button" class="text-gray-500 hover:text-gray-800" @click="removeAt(index)">
                    ×
                </button>
            </span>
        </div>
        <input
            :id="id"
            v-model="draft"
            list="tag-suggestions"
            type="text"
            maxlength="30"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            placeholder="Add a tag and press Enter"
            @keydown="onKeydown"
            @blur="addFromDraft"
        />
        <datalist id="tag-suggestions">
            <option v-for="tag in suggestions" :key="tag.id" :value="tag.name" />
        </datalist>
    </div>
</template>
