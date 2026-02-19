<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

interface FdStat {
    fd_id: number | null;
    fd_nom: string;
    fd_couleur: string | null;
    na: number;
    nc: number;
    total: number;
}

interface MembreEntry {
    id: number;
    nom: string;
    prenom: string;
    telephone: string | null;
    statut_spirituel: string;
    fd_nom: string;
    fd_couleur: string | null;
    faiseur_nom: string | null;
    source: string | null;
}

interface DimancheStats {
    date: string;
    date_formatee: string;
    total_na: number;
    total_nc: number;
    total: number;
    par_fd: FdStat[];
    membres: MembreEntry[];
}

interface Tendance {
    diff: number;
    pct: number;
}

const props = defineProps<{
    dimancheSelectionne: string;
    courant: DimancheStats;
    historique: DimancheStats[];
    tendances: { na: Tendance; nc: Tendance; total: Tendance };
    fds: { id: number; nom: string; couleur: string | null }[];
    userRole: string;
}>();

const selectedDate = ref(props.dimancheSelectionne);
const showMembres = ref(false);
const filtreStatut = ref<string>('tous');

function changerDimanche() {
    router.get(route('rapport-culte'), { date: selectedDate.value }, { preserveState: true });
}

const membresFiltered = computed(() => {
    if (filtreStatut.value === 'tous') return props.courant.membres;
    return props.courant.membres.filter((m: MembreEntry) => m.statut_spirituel === filtreStatut.value);
});

function tendanceIcon(diff: number): string {
    if (diff > 0) return '+';
    if (diff < 0) return '';
    return '=';
}

function tendanceColor(diff: number): string {
    if (diff > 0) return 'text-emerald-600';
    if (diff < 0) return 'text-red-600';
    return 'text-slate-500';
}

function tendanceBg(diff: number): string {
    if (diff > 0) return 'bg-emerald-50 ring-emerald-200';
    if (diff < 0) return 'bg-red-50 ring-red-200';
    return 'bg-slate-50 ring-slate-200';
}

// Trouver la FD la plus performante du dimanche courant
const fdTopPerformer = computed(() => {
    if (!props.courant.par_fd.length) return null;
    return [...props.courant.par_fd].sort((a, b) => b.total - a.total)[0];
});

// Calcul de la barre de progression par FD
function barWidth(val: number, max: number): string {
    if (max === 0) return '0%';
    return `${Math.round((val / max) * 100)}%`;
}

const maxFdTotal = computed(() => {
    return Math.max(...props.courant.par_fd.map((f: FdStat) => f.total), 1);
});

function exportTxt() {
    const c = props.courant;
    const t = props.tendances;
    const lines: string[] = [
        'RAPPORT DE FIN DE CULTE',
        '='.repeat(40),
        `Dimanche : ${c.date_formatee}`,
        '',
        `Nouveaux Arrivants (NA) : ${c.total_na}  (${t.na.diff >= 0 ? '+' : ''}${t.na.diff} vs dim. précédent)`,
        `Nouveaux Convertis (NC) : ${c.total_nc}  (${t.nc.diff >= 0 ? '+' : ''}${t.nc.diff} vs dim. précédent)`,
        `Total accueillis        : ${c.total}  (${t.total.diff >= 0 ? '+' : ''}${t.total.diff} vs dim. précédent)`,
        '',
        '--- REPARTITION PAR FD ---',
    ];
    c.par_fd.forEach((fd: FdStat) => {
        lines.push(`  ${fd.fd_nom.padEnd(25)} NA: ${fd.na}  NC: ${fd.nc}  Total: ${fd.total}`);
    });
    lines.push('', '--- COMPARAISON 4 DERNIERS DIMANCHES ---');
    props.historique.forEach((dim: DimancheStats, idx: number) => {
        lines.push(`  ${dim.date_formatee}${idx === 0 ? ' (actuel)' : ''}  NA: ${dim.total_na}  NC: ${dim.total_nc}  Total: ${dim.total}`);
    });
    if (c.membres.length) {
        lines.push('', `--- MEMBRES ENREGISTRES (${c.membres.length}) ---`);
        c.membres.forEach((m: MembreEntry) => {
            lines.push(`  [${m.statut_spirituel}] ${m.prenom} ${m.nom}  |  FD: ${m.fd_nom}  |  Tel: ${m.telephone || '-'}  |  Faiseur: ${m.faiseur_nom || '-'}`);
        });
    }
    downloadFile(`rapport-culte-${c.date}.txt`, lines.join('\n'));
}

function downloadFile(filename: string, content: string) {
    const blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    URL.revokeObjectURL(url);
}
</script>

<template>
    <Head title="Rapport de fin de culte" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('dashboard')" class="text-slate-400 hover:text-slate-600">&larr;</Link>
                <h1 class="text-lg font-semibold text-slate-800">Rapport de fin de culte</h1>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Sélecteur de dimanche -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-slate-500">Dimanche selectionne</p>
                    <p class="text-base font-semibold text-slate-800 capitalize">{{ courant.date_formatee }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <input
                        v-model="selectedDate"
                        type="date"
                        class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                    <button
                        @click="changerDimanche"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700"
                    >
                        Voir
                    </button>
                    <button
                        @click="exportTxt"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    >
                        Exporter TXT
                    </button>
                </div>
            </div>

            <!-- ═══ Cartes totaux + tendances ═══ -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <!-- Total NA -->
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Nouveaux Arrivants</p>
                            <p class="mt-2 text-3xl font-bold text-amber-600">{{ courant.total_na }}</p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-xl">
                            NA
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-1.5" :class="tendanceColor(tendances.na.diff)">
                        <span class="text-sm font-semibold">{{ tendanceIcon(tendances.na.diff) }}{{ tendances.na.diff }}</span>
                        <span class="text-xs">({{ tendances.na.pct > 0 ? '+' : '' }}{{ tendances.na.pct }}%)</span>
                        <span class="text-xs text-slate-400">vs dim. precedent</span>
                    </div>
                </div>

                <!-- Total NC -->
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Nouveaux Convertis</p>
                            <p class="mt-2 text-3xl font-bold text-blue-600">{{ courant.total_nc }}</p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-xl">
                            NC
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-1.5" :class="tendanceColor(tendances.nc.diff)">
                        <span class="text-sm font-semibold">{{ tendanceIcon(tendances.nc.diff) }}{{ tendances.nc.diff }}</span>
                        <span class="text-xs">({{ tendances.nc.pct > 0 ? '+' : '' }}{{ tendances.nc.pct }}%)</span>
                        <span class="text-xs text-slate-400">vs dim. precedent</span>
                    </div>
                </div>

                <!-- Total -->
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Total accueillis</p>
                            <p class="mt-2 text-3xl font-bold text-slate-800">{{ courant.total }}</p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-xl font-bold text-slate-600">
                            #
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-1.5" :class="tendanceColor(tendances.total.diff)">
                        <span class="text-sm font-semibold">{{ tendanceIcon(tendances.total.diff) }}{{ tendances.total.diff }}</span>
                        <span class="text-xs">({{ tendances.total.pct > 0 ? '+' : '' }}{{ tendances.total.pct }}%)</span>
                        <span class="text-xs text-slate-400">vs dim. precedent</span>
                    </div>
                </div>
            </div>

            <!-- ═══ Comparaison 4 dimanches ═══ -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-slate-500">Comparaison des 4 derniers dimanches</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wider text-slate-500">
                                <th class="pb-3 pr-4 font-medium">Dimanche</th>
                                <th class="pb-3 px-4 font-medium text-center">NA</th>
                                <th class="pb-3 px-4 font-medium text-center">NC</th>
                                <th class="pb-3 pl-4 font-medium text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(dim, idx) in historique"
                                :key="dim.date"
                                class="border-b border-slate-100 transition"
                                :class="idx === 0 ? 'bg-blue-50/50 font-semibold' : ''"
                            >
                                <td class="py-3 pr-4">
                                    <span class="capitalize">{{ dim.date_formatee }}</span>
                                    <span v-if="idx === 0" class="ml-2 inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">Actuel</span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex min-w-[2rem] items-center justify-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700">
                                        {{ dim.total_na }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex min-w-[2rem] items-center justify-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-700">
                                        {{ dim.total_nc }}
                                    </span>
                                </td>
                                <td class="py-3 pl-4 text-center">
                                    <span class="text-base font-bold text-slate-800">{{ dim.total }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mini barres visuelles -->
                <div class="mt-6 space-y-3">
                    <h3 class="text-xs font-medium uppercase tracking-wider text-slate-400">Evolution visuelle</h3>
                    <div v-for="(dim, idx) in historique" :key="'bar-'+dim.date" class="flex items-center gap-3">
                        <span class="w-20 flex-shrink-0 text-xs text-slate-500 text-right">{{ dim.date.substring(5) }}</span>
                        <div class="flex flex-1 h-6 rounded-full bg-slate-100 overflow-hidden">
                            <div
                                class="h-full bg-amber-400 transition-all duration-500"
                                :style="{ width: barWidth(dim.total_na, Math.max(...historique.map(h => h.total), 1)) }"
                            ></div>
                            <div
                                class="h-full bg-blue-400 transition-all duration-500"
                                :style="{ width: barWidth(dim.total_nc, Math.max(...historique.map(h => h.total), 1)) }"
                            ></div>
                        </div>
                        <span class="w-8 text-right text-xs font-bold text-slate-600">{{ dim.total }}</span>
                    </div>
                    <div class="flex items-center gap-4 text-xs text-slate-500">
                        <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-amber-400"></span> NA</span>
                        <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-blue-400"></span> NC</span>
                    </div>
                </div>
            </div>

            <!-- ═══ Detail par FD ═══ -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-slate-500">
                    Repartition par Famille de Disciples
                </h2>

                <div v-if="courant.par_fd.length === 0" class="py-8 text-center text-sm text-slate-400">
                    Aucun NA ou NC enregistre ce dimanche.
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="fd in courant.par_fd"
                        :key="fd.fd_id ?? 'none'"
                        class="rounded-lg border border-slate-100 p-4"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div
                                    class="h-3 w-3 rounded-full"
                                    :style="{ backgroundColor: fd.fd_couleur || '#94a3b8' }"
                                ></div>
                                <span class="text-sm font-semibold text-slate-800">{{ fd.fd_nom }}</span>
                            </div>
                            <span class="text-lg font-bold text-slate-700">{{ fd.total }}</span>
                        </div>
                        <div class="mt-2 flex items-center gap-4">
                            <div class="flex items-center gap-1.5">
                                <span class="inline-flex items-center justify-center rounded bg-amber-100 px-1.5 py-0.5 text-xs font-bold text-amber-700">{{ fd.na }}</span>
                                <span class="text-xs text-slate-500">NA</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="inline-flex items-center justify-center rounded bg-blue-100 px-1.5 py-0.5 text-xs font-bold text-blue-700">{{ fd.nc }}</span>
                                <span class="text-xs text-slate-500">NC</span>
                            </div>
                        </div>
                        <!-- Barre de progression -->
                        <div class="mt-2 h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="flex h-full">
                                <div class="h-full bg-amber-400" :style="{ width: barWidth(fd.na, maxFdTotal) }"></div>
                                <div class="h-full bg-blue-400" :style="{ width: barWidth(fd.nc, maxFdTotal) }"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FD top performer -->
                <div v-if="fdTopPerformer && courant.par_fd.length > 1" class="mt-4 rounded-lg bg-emerald-50 p-3 ring-1 ring-emerald-200">
                    <p class="text-sm text-emerald-800">
                        <span class="font-semibold">{{ fdTopPerformer.fd_nom }}</span> est la FD qui a accueilli le plus de personnes ce dimanche ({{ fdTopPerformer.total }}).
                    </p>
                </div>
            </div>

            <!-- ═══ Liste detaillee des membres ═══ -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-500">
                        Membres enregistres ({{ courant.membres.length }})
                    </h2>
                    <button
                        @click="showMembres = !showMembres"
                        class="rounded-lg px-3 py-1.5 text-xs font-medium text-blue-600 transition hover:bg-blue-50"
                    >
                        {{ showMembres ? 'Masquer' : 'Afficher la liste' }}
                    </button>
                </div>

                <div v-if="showMembres" class="mt-4">
                    <!-- Filtre NA / NC -->
                    <div class="mb-3 flex gap-2">
                        <button
                            @click="filtreStatut = 'tous'"
                            class="rounded-full px-3 py-1 text-xs font-medium transition"
                            :class="filtreStatut === 'tous' ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        >Tous ({{ courant.membres.length }})</button>
                        <button
                            @click="filtreStatut = 'NA'"
                            class="rounded-full px-3 py-1 text-xs font-medium transition"
                            :class="filtreStatut === 'NA' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-700 hover:bg-amber-100'"
                        >NA ({{ courant.total_na }})</button>
                        <button
                            @click="filtreStatut = 'NC'"
                            class="rounded-full px-3 py-1 text-xs font-medium transition"
                            :class="filtreStatut === 'NC' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-700 hover:bg-blue-100'"
                        >NC ({{ courant.total_nc }})</button>
                    </div>

                    <div class="divide-y divide-slate-100 overflow-hidden rounded-lg border border-slate-200">
                        <div
                            v-for="m in membresFiltered"
                            :key="m.id"
                            class="flex items-center gap-3 px-4 py-3 transition hover:bg-slate-50"
                        >
                            <div
                                class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full text-xs font-bold"
                                :class="m.statut_spirituel === 'NA' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700'"
                            >
                                {{ m.prenom?.[0] }}{{ m.nom?.[0] }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <Link :href="route('membres.show', m.id)" class="text-sm font-medium text-slate-800 hover:text-blue-600">
                                    {{ m.prenom }} {{ m.nom }}
                                </Link>
                                <p class="text-xs text-slate-500 truncate">
                                    <span
                                        class="inline-block rounded px-1 py-0.5 text-xs font-bold"
                                        :class="m.statut_spirituel === 'NA' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700'"
                                    >{{ m.statut_spirituel }}</span>
                                    <span v-if="m.fd_nom"> &middot; {{ m.fd_nom }}</span>
                                    <span v-if="m.telephone"> &middot; {{ m.telephone }}</span>
                                    <span v-if="m.faiseur_nom"> &middot; Suivi : {{ m.faiseur_nom }}</span>
                                </p>
                            </div>
                        </div>
                        <div v-if="!membresFiltered.length" class="p-6 text-center text-sm text-slate-400">
                            Aucun membre enregistre pour ce filtre.
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══ Comparaison detaillee par FD sur 4 dimanches ═══ -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-slate-500">
                    Evolution par FD sur 4 dimanches
                </h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wider text-slate-500">
                                <th class="pb-3 pr-4 font-medium">FD</th>
                                <th
                                    v-for="dim in historique"
                                    :key="'th-'+dim.date"
                                    class="pb-3 px-2 text-center font-medium whitespace-nowrap"
                                >
                                    {{ dim.date.substring(5) }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="fd in fds"
                                :key="'fd-evo-'+fd.id"
                                class="border-b border-slate-100"
                            >
                                <td class="py-2.5 pr-4">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: fd.couleur || '#94a3b8' }"></div>
                                        <span class="text-sm font-medium text-slate-700 whitespace-nowrap">{{ fd.nom }}</span>
                                    </div>
                                </td>
                                <td
                                    v-for="dim in historique"
                                    :key="'cell-'+fd.id+'-'+dim.date"
                                    class="py-2.5 px-2 text-center"
                                >
                                    <span class="text-sm font-semibold text-slate-700">
                                        {{ dim.par_fd.find((f: FdStat) => f.fd_id === fd.id)?.total ?? 0 }}
                                    </span>
                                    <span class="block text-xs text-slate-400">
                                        {{ dim.par_fd.find((f: FdStat) => f.fd_id === fd.id)?.na ?? 0 }}na
                                        {{ dim.par_fd.find((f: FdStat) => f.fd_id === fd.id)?.nc ?? 0 }}nc
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
