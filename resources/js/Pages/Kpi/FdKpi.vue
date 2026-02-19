<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Doughnut } from 'vue-chartjs';
import {
    Chart as ChartJS,
    ArcElement,
    Tooltip,
    Legend,
} from 'chart.js';

ChartJS.register(ArcElement, Tooltip, Legend);

const props = defineProps<{
    kpis: any;
    fd: any;
    periode: { debut: string; fin: string };
}>();

const statutsData = {
    labels: ['NA', 'NC', 'Fidèles', 'STAR'],
    datasets: [{
        data: [props.kpis.na, props.kpis.nc, props.kpis.fideles, props.kpis.stars],
        backgroundColor: ['#F59E0B', '#3B82F6', '#10B981', '#8B5CF6'],
    }],
};

const statutsOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom' as const } },
};

function exportTxt() {
    const k = props.kpis;
    const lines: string[] = [
        `KPI - FD ${props.fd?.nom || ''}`,
        '='.repeat(40),
        `Période : ${props.periode.debut} au ${props.periode.fin}`,
        '',
        '--- STATISTIQUES ---',
        `  Total membres      : ${k.total_membres}`,
        `  Membres actifs     : ${k.membres_actifs}`,
        `  Nouveaux ce mois   : ${k.nouveaux}`,
        `  Invitations        : ${k.invitations}`,
        '',
        '--- TAUX ---',
        `  Taux présence      : ${k.taux_presence}%`,
        `  Taux rétention     : ${k.taux_retention}%`,
        `  Absents 3+ sem.    : ${k.absents_3sem}`,
        '',
        '--- SYSTÈME DE POINTS (période) ---',
        `  Total points FD     : ${k.points?.total_periode ?? 0}`,
        '',
        '--- REPARTITION STATUTS ---',
        `  Nouveaux Arrivants (NA) : ${k.na}`,
        `  Nouveaux Convertis (NC) : ${k.nc}`,
        `  Fidèles                 : ${k.fideles}`,
        `  S.T.A.R                 : ${k.stars}`,
    ];
    const blob = new Blob([lines.join('\n')], { type: 'text/plain;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `kpi-fd-${props.fd?.nom || 'fd'}-${props.periode.debut}.txt`;
    a.click();
    URL.revokeObjectURL(url);
}
</script>

<template>
    <Head :title="'KPI - FD ' + fd?.nom" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-slate-800">KPI — FD {{ fd?.nom }}</h1>
                <button @click="exportTxt" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    Exporter TXT
                </button>
            </div>
        </template>

        <div class="space-y-6">
            <!-- En-tête FD -->
            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="h-2" :style="{ backgroundColor: fd?.couleur }"></div>
                <div class="p-4">
                    <h2 class="text-lg font-bold text-slate-800">{{ fd?.nom }}</h2>
                    <p class="text-sm text-slate-500">Période : {{ periode.debut }} au {{ periode.fin }}</p>
                </div>
            </div>

            <!-- Stats principales -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                    <p class="text-xs text-slate-500">Total membres</p>
                    <p class="mt-1 text-2xl font-bold text-slate-800">{{ kpis.total_membres }}</p>
                </div>
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                    <p class="text-xs text-slate-500">Membres actifs</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-600">{{ kpis.membres_actifs }}</p>
                </div>
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                    <p class="text-xs text-slate-500">Nouveaux ce mois</p>
                    <p class="mt-1 text-2xl font-bold text-blue-600">{{ kpis.nouveaux }}</p>
                </div>
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                    <p class="text-xs text-slate-500">Invitations</p>
                    <p class="mt-1 text-2xl font-bold text-purple-600">{{ kpis.invitations }}</p>
                </div>
            </div>

            <!-- Taux -->
            <div class="grid grid-cols-3 gap-3">
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200 text-center">
                    <p class="text-xs text-slate-500">Taux présence</p>
                    <p class="mt-1 text-3xl font-bold" :class="kpis.taux_presence >= 70 ? 'text-emerald-600' : 'text-amber-600'">
                        {{ kpis.taux_presence }}%
                    </p>
                </div>
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200 text-center">
                    <p class="text-xs text-slate-500">Taux rétention</p>
                    <p class="mt-1 text-3xl font-bold" :class="kpis.taux_retention >= 80 ? 'text-emerald-600' : 'text-red-600'">
                        {{ kpis.taux_retention }}%
                    </p>
                </div>
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-red-100 text-center">
                    <p class="text-xs text-slate-500">Absents 3+ sem.</p>
                    <p class="mt-1 text-3xl font-bold text-red-600">{{ kpis.absents_3sem }}</p>
                </div>
            </div>

            <!-- Système de points (période) -->
            <div>
                <h2 class="mb-3 flex items-center gap-2 text-base font-semibold text-slate-700">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-100 text-sm">🏆</span>
                    Système de points
                </h2>
                <p class="mb-3 text-xs text-slate-500">Points attribués aux faiseurs de cette FD sur la période.</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                        <p class="text-xs text-slate-500">Total points (période)</p>
                        <p class="mt-1 text-3xl font-bold text-indigo-600">{{ kpis.points?.total_periode ?? 0 }}</p>
                    </div>
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                        <h3 class="mb-2 text-sm font-semibold text-slate-700">Top faiseurs (points période)</h3>
                        <ul class="space-y-1">
                            <li v-for="(f, idx) in (kpis.points?.classement_faiseurs ?? [])" :key="f.user_id" class="flex justify-between text-sm">
                                <span class="text-slate-700">#{{ idx + 1 }} {{ f.nom }}</span>
                                <span class="font-semibold text-indigo-600">{{ f.total_points }} pts</span>
                            </li>
                            <li v-if="!(kpis.points?.classement_faiseurs?.length)" class="text-slate-500">Aucun point sur la période.</li>
                        </ul>
                    </div>
                </div>
                <div class="mt-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                    <h3 class="mb-2 text-sm font-semibold text-slate-700">Répartition par type d'action</h3>
                    <ul class="flex flex-wrap gap-x-4 gap-y-1 text-sm">
                        <li v-for="a in (kpis.points?.par_action ?? [])" :key="a.action" class="flex gap-2">
                            <span class="text-slate-600">{{ a.label }}</span>
                            <span class="font-medium text-slate-800">{{ a.points }} pts</span>
                        </li>
                        <li v-if="!(kpis.points?.par_action?.length)" class="text-slate-500">—</li>
                    </ul>
                </div>
            </div>

            <!-- Répartition statuts -->
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <h3 class="mb-3 text-sm font-semibold text-slate-700">Répartition des statuts</h3>
                    <div class="h-48">
                        <Doughnut :data="statutsData" :options="statutsOptions" />
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="rounded-xl bg-amber-50 p-4 ring-1 ring-amber-200">
                        <div class="flex justify-between">
                            <span class="text-sm text-amber-700">Nouveaux Arrivants (NA)</span>
                            <span class="text-lg font-bold text-amber-700">{{ kpis.na }}</span>
                        </div>
                    </div>
                    <div class="rounded-xl bg-blue-50 p-4 ring-1 ring-blue-200">
                        <div class="flex justify-between">
                            <span class="text-sm text-blue-700">Nouveaux Convertis (NC)</span>
                            <span class="text-lg font-bold text-blue-700">{{ kpis.nc }}</span>
                        </div>
                    </div>
                    <div class="rounded-xl bg-emerald-50 p-4 ring-1 ring-emerald-200">
                        <div class="flex justify-between">
                            <span class="text-sm text-emerald-700">Fidèles</span>
                            <span class="text-lg font-bold text-emerald-700">{{ kpis.fideles }}</span>
                        </div>
                    </div>
                    <div class="rounded-xl bg-purple-50 p-4 ring-1 ring-purple-200">
                        <div class="flex justify-between">
                            <span class="text-sm text-purple-700">S.T.A.R</span>
                            <span class="text-lg font-bold text-purple-700">{{ kpis.stars }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
