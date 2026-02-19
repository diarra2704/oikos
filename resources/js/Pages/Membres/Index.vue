<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

const props = defineProps<{
    membres: any;
    familles: any[];
    cellules: any[];
    faiseurs: any[];
    filters: {
        search?: string;
        statut?: string;
        fd_id?: string;
        cellule_id?: string;
        suivi_par?: string;
        absent_depuis?: string;
        actif?: string;
    };
    userRole?: string;
    vues: { id: number; nom: string; filtres: Record<string, string | boolean> }[];
}>();

const search = ref(props.filters.search || '');
const statut = ref(props.filters.statut || '');
const fdId = ref(props.filters.fd_id || '');
const celluleId = ref(props.filters.cellule_id || '');
const suiviPar = ref(props.filters.suivi_par || '');
const absentDepuis = ref(props.filters.absent_depuis || '');
const actif = ref(props.filters.actif ?? '');

const showFdFilter = props.familles.length > 1;
const showCelluleFilter = props.cellules.length > 1;
const showFaiseurFilter = props.faiseurs.length > 1;
const vuesList = Array.isArray(props.vues) ? props.vues : [];

const showSaveVueModal = ref(false);
const nomVue = ref('');
const saveVueError = ref('');

const absentOptions = [
    { value: '', label: 'Tous' },
    { value: '3', label: 'Absent 3+ sem.' },
    { value: '6', label: 'Absent 6+ sem.' },
    { value: '12', label: 'Absent 12+ sem.' },
];

let debounceTimer: ReturnType<typeof setTimeout>;
watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 300);
});

watch([statut, fdId, celluleId, suiviPar, absentDepuis, actif], () => {
    applyFilters();
});

function getFilterPayload() {
    return {
        search: search.value || undefined,
        statut: statut.value || undefined,
        fd_id: fdId.value || undefined,
        cellule_id: celluleId.value || undefined,
        suivi_par: suiviPar.value || undefined,
        absent_depuis: absentDepuis.value || undefined,
        actif: actif.value === '' ? undefined : actif.value,
    };
}

function applyFilters(overrides?: Record<string, string | undefined>) {
    router.get(route('membres.index'), { ...getFilterPayload(), ...overrides }, { preserveState: true, replace: true });
}

function loadVue(vue: { filtres: Record<string, string | boolean> }) {
    const f = vue.filtres || {};
    search.value = (f.search as string) ?? '';
    statut.value = (f.statut as string) ?? '';
    fdId.value = f.fd_id != null ? String(f.fd_id) : '';
    celluleId.value = f.cellule_id != null ? String(f.cellule_id) : '';
    suiviPar.value = f.suivi_par != null ? String(f.suivi_par) : '';
    absentDepuis.value = (f.absent_depuis as string) ?? '';
    actif.value = f.actif !== undefined && f.actif !== null ? String(f.actif) : '';
    applyFilters();
}

function openSaveVue() {
    nomVue.value = '';
    saveVueError.value = '';
    showSaveVueModal.value = true;
}

function submitSaveVue() {
    saveVueError.value = '';
    router.post(route('membres.vues.store'), {
        nom: nomVue.value.trim(),
        filtres: getFilterPayload(),
    }, {
        onSuccess: () => { showSaveVueModal.value = false; },
        onError: (errors) => { saveVueError.value = errors.nom ? String(errors.nom) : 'Erreur'; },
    });
}

function deleteVue(id: number) {
    if (confirm('Supprimer cette vue enregistrée ?')) {
        router.delete(route('membres.vues.destroy', id));
    }
}

const statutLabels: Record<string, string> = {
    NA: 'Nouveau Arrivant',
    NC: 'Nouveau Converti',
    fidele: 'Fidèle',
    STAR: 'S.T.A.R',
    faiseur_disciple: 'Faiseur',
};

const statutColors: Record<string, string> = {
    NA: 'bg-amber-100 text-amber-700',
    NC: 'bg-blue-100 text-blue-700',
    fidele: 'bg-emerald-100 text-emerald-700',
    STAR: 'bg-purple-100 text-purple-700',
    faiseur_disciple: 'bg-indigo-100 text-indigo-700',
};

const exportCsvUrl = computed(() => {
    const params = new URLSearchParams();
    const p = getFilterPayload();
    Object.entries(p).forEach(([k, v]) => { if (v !== undefined && v !== '') params.set(k, String(v)); });
    const q = params.toString();
    return route('membres.export.csv') + (q ? '?' + q : '');
});
const exportExcelUrl = computed(() => {
    const params = new URLSearchParams();
    const p = getFilterPayload();
    Object.entries(p).forEach(([k, v]) => { if (v !== undefined && v !== '') params.set(k, String(v)); });
    const q = params.toString();
    return route('membres.export.excel') + (q ? '?' + q : '');
});
</script>

<template>
    <Head title="Membres" />
    <AuthenticatedLayout>
        <template #header><h1 class="text-lg font-semibold text-slate-800">Membres</h1></template>

        <div class="space-y-4">
            <!-- Vues enregistrées -->
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-medium text-slate-500">Vues</span>
                <template v-for="v in vuesList" :key="v.id">
                    <button
                        @click="loadVue(v)"
                        class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700 transition hover:bg-slate-200"
                    >
                        {{ v.nom }}
                    </button>
                    <button
                        @click="deleteVue(v.id)"
                        class="rounded-full p-1 text-slate-400 hover:bg-red-50 hover:text-red-600"
                        title="Supprimer la vue"
                    >
                        ×
                    </button>
                </template>
                <button
                    @click="openSaveVue"
                    class="rounded-full border border-dashed border-slate-300 px-3 py-1 text-xs text-slate-500 hover:border-slate-400 hover:text-slate-700"
                >
                    + Enregistrer la vue actuelle
                </button>
            </div>

            <!-- Barre de recherche + filtres -->
            <div class="flex flex-col gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Rechercher un membre..."
                        class="min-w-[200px] flex-1 rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                    <select v-model="statut" class="rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                        <option value="">Tous statuts</option>
                        <option v-for="(label, key) in statutLabels" :key="key" :value="key">{{ label }}</option>
                    </select>
                    <select v-if="showFdFilter" v-model="fdId" class="rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                        <option value="">Toutes FD</option>
                        <option v-for="fd in familles" :key="fd.id" :value="fd.id">{{ fd.nom }}</option>
                    </select>
                    <select v-if="showCelluleFilter" v-model="celluleId" class="rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                        <option value="">Toutes cellules</option>
                        <option v-for="c in cellules" :key="c.id" :value="c.id">{{ c.nom }}</option>
                    </select>
                    <select v-if="showFaiseurFilter" v-model="suiviPar" class="rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                        <option value="">Tous faiseurs</option>
                        <option v-for="f in faiseurs" :key="f.id" :value="f.id">{{ f.prenom }} {{ f.nom }}</option>
                    </select>
                    <select v-model="absentDepuis" class="rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                        <option v-for="opt in absentOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                    <select v-model="actif" class="rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                        <option value="">Tous (actifs + inactifs)</option>
                        <option value="1">Actifs uniquement</option>
                        <option value="0">Inactifs uniquement</option>
                    </select>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <button
                        @click="openSaveVue"
                        class="text-sm text-slate-500 hover:text-slate-700"
                    >
                        Enregistrer cette vue
                    </button>
                    <div class="flex flex-wrap gap-2">
                        <a
                            :href="exportCsvUrl"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                        >
                            📥 Export CSV
                        </a>
                        <a
                            :href="exportExcelUrl"
                            class="inline-flex items-center justify-center rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-2.5 text-sm font-medium text-emerald-800 transition hover:bg-emerald-100"
                        >
                            📊 Export Excel
                        </a>
                        <Link :href="route('membres.create')" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-blue-700">
                            + Nouveau membre
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Liste -->
            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="divide-y divide-slate-100">
                    <Link
                        v-for="m in membres.data" :key="m.id"
                        :href="route('membres.show', m.id)"
                        class="flex items-center justify-between px-4 py-3 transition hover:bg-slate-50"
                    >
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full text-xs font-bold" :class="statutColors[m.statut_spirituel] || 'bg-slate-100 text-slate-600'">
                                {{ m.prenom?.[0] }}{{ m.nom?.[0] }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-800">{{ m.prenom }} {{ m.nom }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ statutLabels[m.statut_spirituel] || m.statut_spirituel }}
                                    <span v-if="m.famille_disciples"> · {{ m.famille_disciples.nom }}</span>
                                    <span v-if="m.faiseur"> · suivi par {{ m.faiseur.prenom }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="hidden text-right sm:block">
                            <p v-if="m.telephone" class="text-xs text-slate-500">{{ m.telephone }}</p>
                            <p v-if="!m.actif" class="text-xs font-medium text-red-500">Inactif</p>
                        </div>
                    </Link>

                    <div v-if="!membres.data?.length" class="p-8 text-center text-sm text-slate-400">
                        Aucun membre trouvé.
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="membres.links?.length > 3" class="flex justify-center gap-1">
                <template v-for="link in membres.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded-lg px-3 py-2 text-sm"
                        :class="link.active ? 'bg-blue-600 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50'"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>

        <!-- Modal Enregistrer la vue -->
        <div v-show="showSaveVueModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="showSaveVueModal = false">
            <div class="w-full max-w-sm rounded-xl bg-white p-5 shadow-lg">
                <h3 class="text-sm font-semibold text-slate-800">Enregistrer cette vue</h3>
                <p class="mt-1 text-xs text-slate-500">Donnez un nom pour retrouver rapidement ces filtres.</p>
                <input
                    v-model="nomVue"
                    type="text"
                    placeholder="Ex: NC sans faiseur"
                    class="mt-3 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                />
                <p v-if="saveVueError" class="mt-1 text-xs text-red-500">{{ saveVueError }}</p>
                <div class="mt-4 flex justify-end gap-2">
                    <button @click="showSaveVueModal = false" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">Annuler</button>
                    <button @click="submitSaveVue" :disabled="!nomVue.trim()" class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">
                        Enregistrer
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
