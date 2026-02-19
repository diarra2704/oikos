<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps<{ rapport: any }>();

const statutColors: Record<string, string> = {
    soumis: 'bg-amber-100 text-amber-700',
    valide: 'bg-emerald-100 text-emerald-700',
    rejete: 'bg-red-100 text-red-700',
    brouillon: 'bg-slate-100 text-slate-600',
};

function exportTxt() {
    const r = props.rapport;
    const c = r.contenu || {};
    const totalAmes = c.total_ames_suivies ?? null;
    const nbPresentes = Array.isArray(c.ames_presentes) ? c.ames_presentes.length : 0;
    const lines: string[] = [
        `RAPPORT ${(r.type || 'hebdomadaire').toUpperCase()}`,
        '='.repeat(40),
        `Auteur    : ${r.auteur?.prenom ?? ''} ${r.auteur?.nom ?? ''}`,
        `Statut    : ${r.statut}`,
        `Période   : ${r.periode_debut} — ${r.periode_fin}`,
        r.famille_disciples ? `FD        : ${r.famille_disciples.nom}` : '',
        '',
        '--- CONTENU ---',
    ];
    if (totalAmes !== null || nbPresentes > 0) lines.push(`Âmes présentes au culte : ${nbPresentes} / ${totalAmes ?? '?'}`);
    if (c.invitations_abouties !== undefined && c.invitations_faites !== undefined) lines.push(`Invitations abouties : ${c.invitations_abouties} / ${c.invitations_faites}`);
    else if (c.invitations_lancees !== undefined) lines.push(`Invitations (ancien format) : ${c.invitations_lancees}`);
    if (c.immersions_disposes !== undefined && c.immersions_touchees !== undefined) lines.push(`Immersions : ${c.immersions_disposes} / ${c.immersions_touchees}`);
    else if (c.immersions_realisees !== undefined) lines.push(`Immersions (ancien format) : ${c.immersions_realisees}`);
    if (c.actions_semaine) { lines.push('', 'Actions de la semaine :', c.actions_semaine); }
    if (c.difficultes) { lines.push('', 'Difficultés :', c.difficultes); }
    if (c.sujets_priere) { lines.push('', 'Sujets de prière :', c.sujets_priere); }
    if (r.valide_par) {
        lines.push('', `Validé par ${r.valide_par.prenom} ${r.valide_par.nom} le ${r.valide_le}`);
    }
    download(`rapport-${r.type}-${r.periode_debut}.txt`, lines.filter(l => l !== undefined).join('\n'));
}

function download(filename: string, content: string) {
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
    <Head :title="'Rapport ' + rapport.type" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('rapports.index')" class="text-slate-400 hover:text-slate-600">&larr;</Link>
                <h1 class="text-lg font-semibold text-slate-800">Rapport {{ rapport.type }}</h1>
            </div>
        </template>

        <div class="mx-auto max-w-2xl space-y-5">
            <!-- En-tête -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500">Auteur</p>
                        <p class="text-base font-semibold text-slate-800">{{ rapport.auteur?.prenom }} {{ rapport.auteur?.nom }}</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold capitalize" :class="statutColors[rapport.statut] || 'bg-slate-100'">
                        {{ rapport.statut }}
                    </span>
                </div>
                <div class="mt-3 flex gap-6 text-xs text-slate-500">
                    <span>Période : {{ rapport.periode_debut }} — {{ rapport.periode_fin }}</span>
                    <span v-if="rapport.famille_disciples">FD : {{ rapport.famille_disciples.nom }}</span>
                </div>
            </div>

            <!-- Contenu -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 space-y-4">
                <div v-if="rapport.contenu?.total_ames_suivies !== undefined || (rapport.contenu?.ames_presentes && Array.isArray(rapport.contenu.ames_presentes))" class="flex justify-between rounded-lg bg-emerald-50 px-4 py-3">
                    <span class="text-sm text-emerald-700">Âmes présentes au culte</span>
                    <span class="text-lg font-bold text-emerald-700">
                        {{ (rapport.contenu.ames_presentes || []).length }} / {{ rapport.contenu.total_ames_suivies ?? '—' }}
                    </span>
                </div>
                <div v-if="rapport.contenu?.invitations_abouties !== undefined && rapport.contenu?.invitations_faites !== undefined" class="flex justify-between rounded-lg bg-blue-50 px-4 py-3">
                    <span class="text-sm text-blue-700">Invitations abouties</span>
                    <span class="text-lg font-bold text-blue-700">{{ rapport.contenu.invitations_abouties }} / {{ rapport.contenu.invitations_faites }}</span>
                </div>
                <div v-else-if="rapport.contenu?.invitations_lancees !== undefined" class="flex justify-between rounded-lg bg-blue-50 px-4 py-3">
                    <span class="text-sm text-blue-700">Invitations (ancien format)</span>
                    <span class="text-lg font-bold text-blue-700">{{ rapport.contenu.invitations_lancees }}</span>
                </div>
                <div v-if="rapport.contenu?.immersions_disposes !== undefined && rapport.contenu?.immersions_touchees !== undefined" class="flex justify-between rounded-lg bg-purple-50 px-4 py-3">
                    <span class="text-sm text-purple-700">Immersions</span>
                    <span class="text-lg font-bold text-purple-700">{{ rapport.contenu.immersions_disposes }} / {{ rapport.contenu.immersions_touchees }}</span>
                </div>
                <div v-else-if="rapport.contenu?.immersions_realisees !== undefined" class="flex justify-between rounded-lg bg-purple-50 px-4 py-3">
                    <span class="text-sm text-purple-700">Immersions (ancien format)</span>
                    <span class="text-lg font-bold text-purple-700">{{ rapport.contenu.immersions_realisees }}</span>
                </div>
            </div>

            <div v-if="rapport.contenu?.actions_semaine" class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h3 class="mb-2 text-sm font-semibold text-slate-500">Actions de la semaine</h3>
                <p class="text-sm text-slate-700 whitespace-pre-line">{{ rapport.contenu.actions_semaine }}</p>
            </div>
            <div v-if="rapport.contenu?.difficultes" class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h3 class="mb-2 text-sm font-semibold text-slate-500">Difficultés</h3>
                <p class="text-sm text-slate-700 whitespace-pre-line">{{ rapport.contenu.difficultes }}</p>
            </div>
            <div v-if="rapport.contenu?.sujets_priere" class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h3 class="mb-2 text-sm font-semibold text-slate-500">Sujets de prière</h3>
                <p class="text-sm text-slate-700 whitespace-pre-line">{{ rapport.contenu.sujets_priere }}</p>
            </div>

            <div v-if="rapport.valide_par" class="rounded-xl bg-emerald-50 p-4 ring-1 ring-emerald-200">
                <p class="text-sm text-emerald-800">
                    Validé par {{ rapport.valide_par.prenom }} {{ rapport.valide_par.nom }} le {{ rapport.valide_le }}
                </p>
            </div>

            <div class="flex justify-end">
                <button @click="exportTxt" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    📥 Exporter en TXT
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
