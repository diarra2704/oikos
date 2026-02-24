<script setup lang="ts">
import { computed, ref, watch, onMounted, onUnmounted, nextTick } from 'vue';

export interface SearchableOption {
    valeur: string;
    libelle: string;
}

const props = withDefaults(
    defineProps<{
        modelValue: string;
        options: SearchableOption[];
        placeholder?: string;
        emptyLabel?: string;
    }>(),
    {
        placeholder: 'Rechercher...',
        emptyLabel: '—',
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const isOpen = ref(false);
const search = ref('');
const containerRef = ref<HTMLElement | null>(null);

function onDocumentClick(e: MouseEvent) {
    if (containerRef.value && !containerRef.value.contains(e.target as Node)) {
        isOpen.value = false;
    }
}

onMounted(() => document.addEventListener('click', onDocumentClick));
onUnmounted(() => document.removeEventListener('click', onDocumentClick));

const selectedLabel = computed(() => {
    if (!props.modelValue) return props.emptyLabel;
    const o = props.options.find((opt) => opt.valeur === props.modelValue);
    return o?.libelle ?? props.modelValue;
});

const filteredOptions = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.options;
    return props.options.filter((opt) => opt.libelle.toLowerCase().includes(q));
});

function select(value: string) {
    emit('update:modelValue', value);
    search.value = '';
    isOpen.value = false;
}

function open() {
    isOpen.value = true;
    search.value = '';
}

const searchInputRef = ref<HTMLInputElement | null>(null);

watch(isOpen, async (open) => {
    if (open) {
        search.value = '';
        await nextTick();
        searchInputRef.value?.focus();
    }
});
</script>

<template>
    <div ref="containerRef" class="relative w-full">
        <button
            type="button"
            class="flex w-full items-center justify-between rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-left text-sm text-slate-800 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
            @click="open"
        >
            <span class="truncate" :class="{ 'text-slate-400': !modelValue }">{{ selectedLabel }}</span>
            <svg class="ml-2 h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div
            v-show="isOpen"
            class="absolute top-full left-0 right-0 z-50 mt-1 max-h-60 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg"
        >
            <div class="border-b border-slate-100 p-2">
                <input
                    ref="searchInputRef"
                    v-model="search"
                    type="text"
                    class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm placeholder-slate-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    :placeholder="placeholder"
                    autocomplete="off"
                    @keydown.escape="isOpen = false"
                />
            </div>
            <ul class="max-h-48 overflow-y-auto py-1">
                <li>
                    <button
                        type="button"
                        class="w-full px-4 py-2 text-left text-sm hover:bg-slate-50"
                        :class="!modelValue ? 'bg-blue-50 font-medium text-blue-700' : 'text-slate-700'"
                        @click="select('')"
                    >
                        {{ emptyLabel }}
                    </button>
                </li>
                <li
                    v-for="opt in filteredOptions"
                    :key="opt.valeur"
                    class="cursor-pointer"
                >
                    <button
                        type="button"
                        class="w-full px-4 py-2 text-left text-sm hover:bg-slate-50"
                        :class="modelValue === opt.valeur ? 'bg-blue-50 font-medium text-blue-700' : 'text-slate-700'"
                        @click="select(opt.valeur)"
                    >
                        {{ opt.libelle }}
                    </button>
                </li>
                <li v-if="filteredOptions.length === 0" class="px-4 py-3 text-sm text-slate-500">
                    Aucun résultat
                </li>
            </ul>
        </div>
    </div>
</template>
