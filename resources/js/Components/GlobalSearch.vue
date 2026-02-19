<script setup lang="ts">
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

interface SearchResult {
    id: number;
    label: string;
    sublabel?: string | null;
    url: string;
}

interface SearchResponse {
    membres: SearchResult[];
    cellules: SearchResult[];
    familles_disciples: SearchResult[];
}

interface FlatItem {
    url: string;
    label: string;
    sublabel?: string | null;
    section: string;
}

const q = ref('');
const loading = ref(false);
const results = ref<SearchResponse | null>(null);
const open = ref(false);
const selectedIndex = ref(-1);

let debounceTimer: ReturnType<typeof setTimeout> | null = null;

function doSearch() {
    const term = q.value.trim();
    if (term.length < 2) {
        results.value = null;
        open.value = false;
        return;
    }
    loading.value = true;
    open.value = true;
    (globalThis as any).axios
        .get(route('recherche'), { params: { q: term } })
        .then(({ data }: { data: SearchResponse }) => {
            results.value = data;
            selectedIndex.value = 0;
        })
        .finally(() => {
            loading.value = false;
        });
}

watch(q, (val) => {
    if (debounceTimer) clearTimeout(debounceTimer);
    if (!val.trim()) {
        results.value = null;
        open.value = false;
        return;
    }
    debounceTimer = setTimeout(doSearch, 300);
});

const flatItems = computed((): FlatItem[] => {
    const r = results.value;
    if (!r) return [];
    const items: FlatItem[] = [];
    r.membres.forEach((m) => items.push({ ...m, section: 'Membres' }));
    r.cellules.forEach((c) => items.push({ ...c, section: 'Cellules' }));
    r.familles_disciples.forEach((f) => items.push({ ...f, section: 'FD' }));
    return items;
});

const hasResults = computed(() => flatItems.value.length > 0);

const showEmpty = computed(
    () => open.value && results.value !== null && q.value.trim().length >= 2 && !hasResults.value && !loading.value
);

function goTo(url: string) {
    open.value = false;
    q.value = '';
    results.value = null;
    router.visit(url);
}

function onKeydown(e: KeyboardEvent) {
    if (!open.value || flatItems.value.length === 0) {
        if (e.key === 'Escape') open.value = false;
        return;
    }
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        selectedIndex.value = Math.min(selectedIndex.value + 1, flatItems.value.length - 1);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        selectedIndex.value = Math.max(selectedIndex.value - 1, 0);
    } else if (e.key === 'Enter' && selectedIndex.value >= 0 && flatItems.value[selectedIndex.value]) {
        e.preventDefault();
        goTo(flatItems.value[selectedIndex.value].url);
    } else if (e.key === 'Escape') {
        open.value = false;
    }
}

function selectItem(index: number) {
    selectedIndex.value = index;
}

function handleClickOutside(e: MouseEvent) {
    const el = (e.target as HTMLElement).closest('.global-search-wrap');
    if (!el) open.value = false;
}

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));
</script>

<template>
    <div class="global-search-wrap relative max-w-xl flex-1">
        <div class="relative">
            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">🔍</span>
            <input
                v-model="q"
                type="search"
                placeholder="Rechercher un membre, une cellule..."
                class="w-full rounded-lg border-slate-300 py-2 pl-9 pr-4 text-sm focus:border-blue-500 focus:ring-blue-500"
                autocomplete="off"
                @keydown="onKeydown"
                @focus="q.trim().length >= 2 && (open = true)"
            />
            <span
                v-if="loading"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"
            >
                <span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-slate-300 border-t-blue-500" />
            </span>
        </div>

        <Transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-show="open && (loading || hasResults || showEmpty)"
                class="absolute left-0 right-0 top-full z-50 mt-1 max-h-80 overflow-auto rounded-xl border border-slate-200 bg-white py-2 shadow-lg"
                @click.stop
            >
                <div v-if="loading && !results" class="px-4 py-3 text-center text-sm text-slate-500">
                    Recherche...
                </div>
                <div v-else-if="showEmpty" class="px-4 py-3 text-center text-sm text-slate-500">
                    Aucun résultat
                </div>
                <template v-else>
                    <template v-for="(item, index) in flatItems" :key="index">
                        <p
                            v-if="index === 0 || flatItems[index - 1].section !== item.section"
                            class="px-3 py-1 text-xs font-semibold uppercase text-slate-400"
                        >
                            {{ item.section }}
                        </p>
                        <button
                            type="button"
                            class="flex w-full flex-col items-start px-4 py-2 text-left hover:bg-slate-50"
                            :class="selectedIndex === index ? 'bg-blue-50' : ''"
                            @click="goTo(item.url)"
                            @mouseenter="selectItem(index)"
                        >
                            <span class="font-medium text-slate-800">{{ item.label }}</span>
                            <span v-if="item.sublabel" class="text-xs text-slate-500">{{ item.sublabel }}</span>
                        </button>
                    </template>
                </template>
            </div>
        </Transition>
    </div>
</template>
