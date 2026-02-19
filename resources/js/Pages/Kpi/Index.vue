<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Bar, Doughnut, Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    ArcElement,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, PointElement, LineElement, Title, Tooltip, Legend, Filler);

const props = defineProps<{
    kpis: any;
    periode: { debut: string; fin: string };
}>();

// ── Graphiques ──

const tendancesData = {
    labels: props.kpis.tendances.map((t: any) => t.label),
    datasets: [
        {
            label: 'Nouveaux membres',
            data: props.kpis.tendances.map((t: any) => t.nouveaux_membres),
            borderColor: '#3B82F6',
            backgroundColor: '#3B82F620',
            fill: true,
            tension: 0.4,
        },
        {
            label: 'Présences',
            data: props.kpis.tendances.map((t: any) => t.presences),
            borderColor: '#10B981',
            backgroundColor: '#10B98120',
            fill: true,
            tension: 0.4,
        },
        {
            label: 'Invitations',
            data: props.kpis.tendances.map((t: any) => t.invitations),
            borderColor: '#F59E0B',
            backgroundColor: '#F59E0B20',
            fill: true,
            tension: 0.4,
        },
    ],
};

const tendancesOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom' as const } },
    scales: { y: { beginAtZero: true } },
};

const statutsData = {
    labels: Object.keys(props.kpis.transformation.repartition_statuts),
    datasets: [{
        data: Object.values(props.kpis.transformation.repartition_statuts) as number[],
        backgroundColor: ['#F59E0B', '#3B82F6', '#10B981', '#8B5CF6', '#6366F1'],
    }],
};

const statutsOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom' as const } },
};

const comparatifData = {
    labels: props.kpis.par_fd.map((fd: any) => fd.nom),
    datasets: [
        {
            label: 'Membres',
            data: props.kpis.par_fd.map((fd: any) => fd.total_membres),
            backgroundColor: props.kpis.par_fd.map((fd: any) => fd.couleur + '80'),
            borderColor: props.kpis.par_fd.map((fd: any) => fd.couleur),
            borderWidth: 2,
        },
    ],
};

const comparatifOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true } },
};

// Points : répartition par type d'action
const pointsParActionData = computed(() => ({
    labels: (props.kpis.points?.par_action || []).map((a: any) => a.label),
    datasets: [{
        label: 'Points',
        data: (props.kpis.points?.par_action || []).map((a: any) => a.points),
        backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899', '#6366F1', '#14B8A6', '#F97316'],
    }],
}));

const pointsParActionOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom' as const } },
    indexAxis: 'y' as const,
    scales: { x: { beginAtZero: true } },
};

function exportTxt() {
    const k = props.kpis;
    const lines: string[] = [
        'INDICATEURS CLES (KPI) - GLOBAL',
        '='.repeat(45),
        `Période : ${props.periode.debut} au ${props.periode.fin}`,
        '',
        '--- AXE CROISSANCE ---',
        `  Nouveaux membres       : ${k.croissance.nouveaux_membres}`,
        `  Invitations            : ${k.croissance.invitations}`,
        `  Venues sur invitation  : ${k.croissance.invitations_venues}`,
        `  Taux conversion invit. : ${k.croissance.taux_conversion_invitation}%`,
        '',
        '--- AXE FIDELISATION ---',
        `  Taux rétention         : ${k.fidelisation.taux_retention}%`,
        `  Taux présence          : ${k.fidelisation.taux_presence}%`,
        `  Membres actifs         : ${k.fidelisation.membres_actifs}`,
        `  Absents 3+ semaines    : ${k.fidelisation.absents_3_semaines}`,
        `  Absents 8+ semaines    : ${k.fidelisation.absents_8_semaines}`,
        '',
        '--- AXE TRANSFORMATION ---',
        `  Témoignages soumis     : ${k.transformation.temoignages}`,
        `  Témoignages validés    : ${k.transformation.temoignages_valides}`,
        '  Répartition statuts :',
    ];
    for (const [statut, nb] of Object.entries(k.transformation.repartition_statuts)) {
        lines.push(`    ${statut.padEnd(20)} : ${nb}`);
    }
    lines.push(
        '',
        '--- AXE DEPLOIEMENT ---',
        `  Faiseurs actifs        : ${k.deploiement.total_faiseurs}`,
        `  Leaders cellule        : ${k.deploiement.total_leaders}`,
        `  Rapports soumis        : ${k.deploiement.rapports_soumis}`,
        '',
        '--- SYSTÈME DE POINTS (période) ---',
        `  Total points           : ${k.points?.total_periode ?? 0}`,
        '',
        '--- COMPARATIF PAR FD ---',
    );
    k.par_fd.forEach((fd: any) => {
        lines.push(`  ${fd.nom.padEnd(20)} Membres: ${fd.total_membres}  Actifs: ${fd.membres_actifs}  Nouveaux: ${fd.nouveaux}  Points: ${fd.points_total ?? 0}  Présence: ${fd.taux_presence}%  Rétention: ${fd.taux_retention}%`);
    });
    lines.push('', '--- TENDANCES (6 MOIS) ---');
    k.tendances.forEach((t: any) => {
        lines.push(`  ${t.label.padEnd(12)} Nouveaux: ${t.nouveaux_membres}  Présences: ${t.presences}  Invitations: ${t.invitations}`);
    });
    downloadFile(`kpi-global-${props.periode.debut}.txt`, lines.join('\n'));
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
    <Head title="KPI - Indicateurs" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-slate-800">Indicateurs Clés (KPI)</h1>
                <button @click="exportTxt" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    Exporter TXT
                </button>
            </div>
        </template>

        <div class="space-y-6">
            <!-- ═══ Axe 1 : Croissance ═══ -->
            <div>
                <h2 class="mb-3 flex items-center gap-2 text-base font-semibold text-slate-700">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-sm">📈</span>
                    Axe Croissance
                </h2>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                        <p class="text-xs text-slate-500">Nouveaux membres</p>
                        <p class="mt-1 text-2xl font-bold text-emerald-600">{{ kpis.croissance.nouveaux_membres }}</p>
                    </div>
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                        <p class="text-xs text-slate-500">Invitations</p>
                        <p class="mt-1 text-2xl font-bold text-blue-600">{{ kpis.croissance.invitations }}</p>
                    </div>
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                        <p class="text-xs text-slate-500">Venues sur invitation</p>
                        <p class="mt-1 text-2xl font-bold text-purple-600">{{ kpis.croissance.invitations_venues }}</p>
                    </div>
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                        <p class="text-xs text-slate-500">Taux conversion</p>
                        <p class="mt-1 text-2xl font-bold text-amber-600">{{ kpis.croissance.taux_conversion_invitation }}%</p>
                    </div>
                </div>
            </div>

            <!-- ═══ Axe 2 : Fidélisation ═══ -->
            <div>
                <h2 class="mb-3 flex items-center gap-2 text-base font-semibold text-slate-700">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 text-sm">🔒</span>
                    Axe Fidélisation
                </h2>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                        <p class="text-xs text-slate-500">Taux rétention</p>
                        <p class="mt-1 text-2xl font-bold" :class="kpis.fidelisation.taux_retention >= 80 ? 'text-emerald-600' : 'text-red-600'">
                            {{ kpis.fidelisation.taux_retention }}%
                        </p>
                    </div>
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                        <p class="text-xs text-slate-500">Taux présence</p>
                        <p class="mt-1 text-2xl font-bold" :class="kpis.fidelisation.taux_presence >= 70 ? 'text-emerald-600' : 'text-amber-600'">
                            {{ kpis.fidelisation.taux_presence }}%
                        </p>
                    </div>
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                        <p class="text-xs text-slate-500">Actifs</p>
                        <p class="mt-1 text-2xl font-bold text-slate-800">{{ kpis.fidelisation.membres_actifs }}</p>
                    </div>
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-amber-100">
                        <p class="text-xs text-slate-500">Absents 3+ sem.</p>
                        <p class="mt-1 text-2xl font-bold text-amber-600">{{ kpis.fidelisation.absents_3_semaines }}</p>
                    </div>
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-red-100">
                        <p class="text-xs text-slate-500">Absents 8+ sem.</p>
                        <p class="mt-1 text-2xl font-bold text-red-600">{{ kpis.fidelisation.absents_8_semaines }}</p>
                    </div>
                </div>
            </div>

            <!-- ═══ Axe 3 : Transformation ═══ -->
            <div>
                <h2 class="mb-3 flex items-center gap-2 text-base font-semibold text-slate-700">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-100 text-sm">✨</span>
                    Axe Transformation
                </h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                        <p class="mb-3 text-sm font-medium text-slate-700">Répartition des statuts spirituels</p>
                        <div class="h-48">
                            <Doughnut :data="statutsData" :options="statutsOptions" />
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                            <p class="text-xs text-slate-500">Témoignages soumis</p>
                            <p class="mt-1 text-2xl font-bold text-purple-600">{{ kpis.transformation.temoignages }}</p>
                        </div>
                        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                            <p class="text-xs text-slate-500">Témoignages validés</p>
                            <p class="mt-1 text-2xl font-bold text-emerald-600">{{ kpis.transformation.temoignages_valides }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══ Axe 4 : Déploiement ═══ -->
            <div>
                <h2 class="mb-3 flex items-center gap-2 text-base font-semibold text-slate-700">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-sm">🚀</span>
                    Axe Déploiement
                </h2>
                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                        <p class="text-xs text-slate-500">Faiseurs actifs</p>
                        <p class="mt-1 text-2xl font-bold text-blue-600">{{ kpis.deploiement.total_faiseurs }}</p>
                    </div>
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                        <p class="text-xs text-slate-500">Leaders cellule</p>
                        <p class="mt-1 text-2xl font-bold text-purple-600">{{ kpis.deploiement.total_leaders }}</p>
                    </div>
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                        <p class="text-xs text-slate-500">Rapports soumis</p>
                        <p class="mt-1 text-2xl font-bold text-emerald-600">{{ kpis.deploiement.rapports_soumis }}</p>
                    </div>
                </div>
            </div>

            <!-- ═══ Système de points (période) ═══ -->
            <div>
                <h2 class="mb-3 flex items-center gap-2 text-base font-semibold text-slate-700">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-100 text-sm">🏆</span>
                    Système de points
                </h2>
                <p class="mb-3 text-xs text-slate-500">Points attribués sur la période (présence culte, invitations, rapports, formations, famille d'impact, service…).</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                        <p class="text-xs text-slate-500">Total points (période)</p>
                        <p class="mt-1 text-3xl font-bold text-indigo-600">{{ kpis.points?.total_periode ?? 0 }}</p>
                    </div>
                    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                        <h3 class="mb-3 text-sm font-semibold text-slate-700">Répartition par type d'action</h3>
                        <div v-if="(kpis.points?.par_action?.length ?? 0) > 0" class="h-56">
                            <Bar :data="pointsParActionData" :options="pointsParActionOptions" />
                        </div>
                        <p v-else class="text-sm text-slate-500">Aucun point sur la période.</p>
                    </div>
                </div>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                        <div class="border-b border-slate-100 bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-700">Top 10 faiseurs (points période)</div>
                        <ul class="divide-y divide-slate-100">
                            <li v-for="(f, idx) in (kpis.points?.classement_faiseurs ?? [])" :key="f.user_id" class="flex items-center justify-between px-4 py-2">
                                <span class="text-sm text-slate-700"><span class="font-medium text-slate-500">#{{ idx + 1 }}</span> {{ f.nom }}</span>
                                <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-sm font-semibold text-indigo-700">{{ f.total_points }} pts</span>
                            </li>
                            <li v-if="!(kpis.points?.classement_faiseurs?.length)" class="px-4 py-3 text-sm text-slate-500">Aucun point sur la période.</li>
                        </ul>
                    </div>
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                        <h3 class="mb-2 text-sm font-semibold text-slate-700">Détail par action</h3>
                        <ul class="space-y-1 text-sm">
                            <li v-for="a in (kpis.points?.par_action ?? [])" :key="a.action" class="flex justify-between">
                                <span class="text-slate-600">{{ a.label }}</span>
                                <span class="font-medium text-slate-800">{{ a.points }} pts ({{ a.count }})</span>
                            </li>
                            <li v-if="!(kpis.points?.par_action?.length)" class="text-slate-500">—</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- ═══ Tendances 6 mois ═══ -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="mb-4 text-base font-semibold text-slate-700">Tendances (6 derniers mois)</h2>
                <div class="h-64">
                    <Line :data="tendancesData" :options="tendancesOptions" />
                </div>
            </div>

            <!-- ═══ Comparatif inter-FD ═══ -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="mb-4 text-base font-semibold text-slate-700">Comparatif par Famille de Disciples</h2>
                <div class="h-64">
                    <Bar :data="comparatifData" :options="comparatifOptions" />
                </div>
            </div>

            <!-- ═══ Tableau détaillé par FD ═══ -->
            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                            <tr>
                                <th class="px-4 py-3">FD</th>
                                <th class="px-4 py-3 text-center">Membres</th>
                                <th class="px-4 py-3 text-center">Actifs</th>
                                <th class="px-4 py-3 text-center">Nouveaux</th>
                                <th class="px-4 py-3 text-center">Points</th>
                                <th class="px-4 py-3 text-center">Présence</th>
                                <th class="px-4 py-3 text-center">Rétention</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="fd in kpis.par_fd" :key="fd.id" class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="h-3 w-3 rounded-full" :style="{ backgroundColor: fd.couleur }"></div>
                                        <span class="font-medium text-slate-800">{{ fd.nom }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center font-semibold">{{ fd.total_membres }}</td>
                                <td class="px-4 py-3 text-center">{{ fd.membres_actifs }}</td>
                                <td class="px-4 py-3 text-center text-emerald-600 font-semibold">{{ fd.nouveaux }}</td>
                                <td class="px-4 py-3 text-center font-semibold text-indigo-600">{{ fd.points_total ?? 0 }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="fd.taux_presence >= 70 ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                                        {{ fd.taux_presence }}%
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="fd.taux_retention >= 80 ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'">
                                        {{ fd.taux_retention }}%
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
