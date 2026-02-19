<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

const props = defineProps<{
    viewMode?: 'modifs' | 'audit';
    items: {
        type: string;
        id: number;
        label: string;
        sublabel?: string;
        created_at?: string;
        updated_at?: string;
        created_by?: string | null;
        updated_by?: string | null;
        url: string | null;
        action?: string;
        auditable_type_label?: string;
        old_values?: Record<string, unknown>;
        new_values?: Record<string, unknown>;
    }[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };
    links: { url: string | null; label: string; active: boolean }[];
    filters: { type: string };
}>();

const viewMode = ref(props.viewMode ?? 'modifs');
const typeFilter = ref(props.filters?.type ?? '');
watch(() => [props.viewMode, props.filters?.type], () => {
    viewMode.value = props.viewMode ?? 'modifs';
    typeFilter.value = props.filters?.type ?? '';
}, { immediate: false });

const typeOptionsModifs = [
    { value: '', label: 'Tous' },
    { value: 'membre', label: 'Membres' },
    { value: 'cellule', label: 'Cellules' },
    { value: 'transfert', label: 'Transferts' },
];
const typeOptionsAudit = [
    { value: '', label: 'Tous' },
    { value: 'membre', label: 'Membres' },
    { value: 'cellule', label: 'Cellules' },
    { value: 'transfert', label: 'Transferts' },
    { value: 'rapport', label: 'Rapports' },
    { value: 'rapport_mensuel', label: 'Rapports mensuels' },
    { value: 'famille_impact', label: 'Familles d\'impact' },
    { value: 'parametre', label: 'Paramètres' },
];
const typeOptions = computed(() => viewMode.value === 'audit' ? typeOptionsAudit : typeOptionsModifs);

function applyFilter() {
    router.get(route('historique.index'), {
        type: typeFilter.value || undefined,
        view: viewMode.value,
    }, { preserveState: false });
}

function setView(mode: 'modifs' | 'audit') {
    viewMode.value = mode;
    router.get(route('historique.index'), {
        type: typeFilter.value || undefined,
        view: mode,
    }, { preserveState: false });
}

const typeLabels: Record<string, string> = {
    membre: 'Membre',
    cellule: 'Cellule',
    transfert: 'Transfert',
    audit: 'Audit',
    rapport: 'Rapport',
    rapport_mensuel: 'Rapport mensuel',
    famille_impact: 'Famille d\'impact',
    parametre: 'Paramètre',
};

const typeIcons: Record<string, string> = {
    membre: '👤',
    cellule: '👥',
    transfert: '🔄',
    audit: '📋',
    rapport: '📄',
    rapport_mensuel: '📅',
    famille_impact: '🏠',
    parametre: '⚙️',
};

function formatDate(iso: string | undefined): string {
    if (!iso) return '—';
    return new Date(iso).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <Head title="Dernières modifications" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-lg font-semibold text-slate-800">Dernières modifications</h1>
        </template>

        <div class="space-y-4">
            <p class="text-sm text-slate-500">
                Dernières modifications sur les entités, ou journal d'audit complet (qui a fait quoi et quand).
            </p>

            <div class="flex flex-wrap items-center gap-3">
                <span class="text-sm font-medium text-slate-600">Vue</span>
                <div class="flex rounded-lg border border-slate-300 p-0.5">
                    <button
                        type="button"
                        @click="setView('modifs')"
                        class="rounded-md px-3 py-1.5 text-sm font-medium transition"
                        :class="viewMode === 'modifs' ? 'bg-blue-600 text-white' : 'text-slate-600 hover:bg-slate-100'"
                    >
                        Dernières modifications
                    </button>
                    <button
                        type="button"
                        @click="setView('audit')"
                        class="rounded-md px-3 py-1.5 text-sm font-medium transition"
                        :class="viewMode === 'audit' ? 'bg-blue-600 text-white' : 'text-slate-600 hover:bg-slate-100'"
                    >
                        Journal d'audit
                    </button>
                </div>
                <label for="filter-type" class="text-sm font-medium text-slate-600">Type</label>
                <select
                    id="filter-type"
                    v-model="typeFilter"
                    class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                    @change="applyFilter"
                >
                    <option v-for="opt in typeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
            </div>

            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                <ul class="divide-y divide-slate-100">
                    <li v-for="(item, i) in items" :key="(item.type + '-' + item.id) + (item.action || '')" class="flex items-start gap-3 px-4 py-3">
                        <span class="text-xl">{{ typeIcons[item.type] || (item.auditable_type_label ? typeIcons[item.auditable_type_label.toLowerCase()] : null) || '•' }}</span>
                        <div class="min-w-0 flex-1">
                            <Link v-if="item.url" :href="item.url" class="font-medium text-slate-800 hover:text-blue-600">
                                {{ item.label }}
                            </Link>
                            <span v-else class="font-medium text-slate-800">{{ item.label }}</span>
                            <p v-if="item.sublabel" class="text-xs text-slate-500">{{ item.sublabel }}</p>
                            <p class="mt-0.5 text-xs text-slate-400">
                                <span v-if="item.updated_by">Modifié par {{ item.updated_by }}</span>
                                <template v-else-if="item.created_by">Par {{ item.created_by }}</template>
                                <template v-else>—</template>
                                <span v-if="item.updated_at" class="ml-1">· {{ formatDate(item.updated_at) }}</span>
                                <span v-else-if="item.created_at" class="ml-1">· {{ formatDate(item.created_at) }}</span>
                            </p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                            {{ item.auditable_type_label || typeLabels[item.type] || item.type }}
                        </span>
                    </li>
                </ul>
                <div v-if="!items?.length" class="p-8 text-center text-sm text-slate-400">
                    {{ viewMode === 'audit' ? 'Aucune entrée dans le journal d\'audit.' : 'Aucune modification récente.' }}
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="meta?.last_page > 1" class="flex flex-col items-center gap-3 sm:flex-row sm:justify-between">
                <p class="text-sm text-slate-500">
                    Affichage de {{ meta.from }} à {{ meta.to }} sur {{ meta.total }} entrée(s).
                </p>
                <div class="flex flex-wrap justify-center gap-1">
                    <template v-for="(link, idx) in links" :key="idx">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="rounded-lg px-3 py-2 text-sm"
                            :class="link.active ? 'bg-blue-600 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50'"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="rounded-lg px-3 py-2 text-sm"
                            :class="link.active ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-400 cursor-default'"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
