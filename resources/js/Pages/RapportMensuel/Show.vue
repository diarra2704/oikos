<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    rapport: any;
    moisFormate: string;
    userRole: string;
}>();

const d = computed(() => props.rapport.donnees || {});
const integration = computed(() => d.value.integration || {});
const listeAmes = computed(() => d.value.liste_ames || []);
const amesSuivies = computed(() => listeAmes.value.filter((a: any) => a.actif));
const amesSorties = computed(() => listeAmes.value.filter((a: any) => !a.actif));

const niveauLabels: Record<string, string> = {
    decouverte: 'Decouverte, entretiens de base',
    progression: 'Formations, integration en cours',
    consolidation: 'Engagement ferme et fidelite',
    confirme: 'Totalement integres et sert',
};

const motifLabels: Record<string, string> = {
    injoignable: 'Injoignables',
    ne_repond_plus: 'Ne repond plus',
    en_deplacement: 'En deplacement',
    autre: 'Autre motif',
};

function fraction(val: number, total: number): string {
    return `${String(val).padStart(2, '0')}/${String(total).padStart(2, '0')}`;
}

function exportTxt() {
    const r = props.rapport;
    const dd = d.value;
    const integ = integration.value;
    const pad = (v: number) => String(v ?? 0).padStart(2, '0');
    const lines: string[] = [
        'RAPPORT SYNTHETIQUE DE SUIVI DES NA/NC',
        '='.repeat(45),
        `Mois de       : ${props.moisFormate}`,
        `Faiseur       : ${r.faiseur?.prenom ?? ''} ${r.faiseur?.nom ?? ''}`,
        r.famille_disciples ? `FD            : ${r.famille_disciples.nom}` : '',
        '',
        `Total âmes reçues : ${dd.total}`,
        `  A. Âmes encore suivies  : ${pad(dd.suivies)}/${pad(dd.total)}`,
        `  B. Âmes sorties du suivi: ${pad(dd.sorties)}/${pad(dd.total)}`,
        '',
        '--- A. PARMI LES AMES ENCORE SUIVIES ---',
        `  1. Déjà dans le PCNC    : ${pad(dd.a_en_pcnc)}/${pad(dd.suivies)}`,
        `  2. Dans une FI           : ${pad(dd.a_en_fi)}/${pad(dd.suivies)}`,
        `  3. Régulier à l'église   : ${pad(dd.a_regulier)}/${pad(dd.suivies)}`,
        '',
        "--- NIVEAU D'INTEGRATION ---",
        `  En début de parcours     : ${pad(integ.decouverte ?? 0)}/${pad(dd.total)}`,
        `  En progression           : ${pad(integ.progression ?? 0)}/${pad(dd.total)}`,
        `  En consolidation         : ${pad(integ.consolidation ?? 0)}/${pad(dd.total)}`,
        `  Disciples confirmés      : ${pad(integ.confirme ?? 0)}/${pad(dd.total)}`,
        '',
        '--- B. PARMI LES AMES SORTIES DU SUIVI ---',
        `  1. Injoignables          : ${pad(dd.b_injoignables)}/${pad(dd.sorties)}`,
        `  2. Ne répond plus        : ${pad(dd.b_ne_repond_plus)}/${pad(dd.sorties)}`,
        `  3. En déplacement        : ${pad(dd.b_en_deplacement)}/${pad(dd.sorties)}`,
        `  4. Autre motif           : ${pad(dd.b_autre)}/${pad(dd.sorties)}`,
        '',
        '--- LISTE DES AMES ---',
    ];
    amesSuivies.value.forEach((a: any) => {
        const tags = [a.en_pcnc ? 'PCNC' : '', a.en_fi ? 'FI' : '', a.regulier_eglise ? 'Régulier' : ''].filter(Boolean).join(', ');
        lines.push(`  [Suivie] ${a.prenom} ${a.nom} (${a.niveau_integration || '?'})${tags ? ' - ' + tags : ''}`);
    });
    amesSorties.value.forEach((a: any) => {
        lines.push(`  [Sortie] ${a.prenom} ${a.nom} - motif: ${(a.motif_sortie || '?').replace('_', ' ')}`);
    });
    if (r.observations) { lines.push('', '--- OBSERVATIONS ---', r.observations); }
    lines.push('', 'Gloire à Jésus');
    downloadFile(`rapport-mensuel-${r.mois}.txt`, lines.filter(l => l !== undefined).join('\n'));
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
    <Head :title="'Rapport - ' + moisFormate" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('rapport-mensuel.index')" class="text-slate-400 hover:text-slate-600">&larr;</Link>
                <h1 class="text-lg font-semibold text-slate-800">Rapport mensuel</h1>
            </div>
        </template>

        <div class="mx-auto max-w-2xl space-y-5">
            <!-- ═══ EN-TETE DU RAPPORT ═══ -->
            <div class="rounded-2xl bg-gradient-to-br from-blue-700 to-blue-800 p-6 text-white shadow-lg">
                <p class="text-xs font-semibold uppercase tracking-widest opacity-70">Rapport Synthetique de Suivi des NA/NC</p>
                <p class="mt-2 text-lg font-bold">Mois de : {{ moisFormate }}</p>
                <p class="mt-1 text-sm opacity-90">Faiseur : {{ rapport.faiseur?.prenom }} {{ rapport.faiseur?.nom }}</p>
                <p v-if="rapport.famille_disciples" class="text-xs opacity-70">FD : {{ rapport.famille_disciples.nom }}</p>
            </div>

            <!-- ═══ TOTAL AMES ═══ -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-xl font-bold text-slate-700">
                        {{ d.total }}
                    </div>
                    <div>
                        <p class="text-base font-bold text-slate-800">Total ames recues jusqu'a ce jour : {{ d.total }}</p>
                    </div>
                </div>

                <div class="mt-4 space-y-2 pl-4 border-l-2 border-slate-200">
                    <div class="flex items-center gap-3">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700">A</span>
                        <p class="text-sm text-slate-700">Ames encore suivies : <span class="font-bold text-emerald-700">{{ fraction(d.suivies, d.total) }}</span></p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-red-100 text-xs font-bold text-red-700">B</span>
                        <p class="text-sm text-slate-700">Ames sorties du suivi : <span class="font-bold text-red-700">{{ fraction(d.sorties, d.total) }}</span></p>
                    </div>
                </div>

                <!-- Barre visuelle -->
                <div class="mt-4 h-3 rounded-full bg-slate-100 overflow-hidden">
                    <div class="flex h-full">
                        <div class="h-full bg-emerald-500 transition-all" :style="{ width: d.total > 0 ? `${(d.suivies / d.total) * 100}%` : '0%' }"></div>
                        <div class="h-full bg-red-400 transition-all" :style="{ width: d.total > 0 ? `${(d.sorties / d.total) * 100}%` : '0%' }"></div>
                    </div>
                </div>
            </div>

            <!-- ═══ A. AMES ENCORE SUIVIES ═══ -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-600 text-xs font-bold text-white">A</span>
                    Parmi les ames encore suivies : {{ d.suivies }}
                </h2>

                <div class="mt-4 space-y-3">
                    <div class="flex items-center justify-between rounded-lg bg-purple-50 p-3">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-purple-800">1. Deja dans le PCNC</span>
                        </div>
                        <span class="text-sm font-bold text-purple-800">{{ fraction(d.a_en_pcnc, d.suivies) }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-indigo-50 p-3">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-indigo-800">2. Dans une FI</span>
                        </div>
                        <span class="text-sm font-bold text-indigo-800">{{ fraction(d.a_en_fi, d.suivies) }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-cyan-50 p-3">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-cyan-800">3. Regulier a l'eglise</span>
                        </div>
                        <span class="text-sm font-bold text-cyan-800">{{ fraction(d.a_regulier, d.suivies) }}</span>
                    </div>
                </div>

                <!-- Liste noms âmes suivies -->
                <div v-if="amesSuivies.length" class="mt-4 border-t border-slate-100 pt-3">
                    <p class="mb-2 text-xs font-medium text-slate-500">Detail :</p>
                    <div class="flex flex-wrap gap-1.5">
                        <span v-for="a in amesSuivies" :key="a.id"
                            class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-800 ring-1 ring-emerald-200">
                            {{ a.prenom }} {{ a.nom }}
                            <span v-if="a.en_pcnc" class="text-purple-600" title="PCNC">P</span>
                            <span v-if="a.en_fi" class="text-indigo-600" title="FI">F</span>
                            <span v-if="a.regulier_eglise" class="text-cyan-600" title="Regulier">R</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- ═══ NIVEAU D'INTEGRATION ═══ -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-sm font-bold text-slate-800">Niveau d'integration des ames activement suivies</h2>

                <div class="mt-4 space-y-3">
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-amber-800">En debut de parcours</span>
                            <span class="text-sm font-bold text-amber-800">{{ fraction(integration.decouverte ?? 0, d.total) }}</span>
                        </div>
                        <p class="mt-0.5 text-xs text-amber-600">Decouverte, entretiens de base</p>
                        <div class="mt-2 h-2 rounded-full bg-amber-100">
                            <div class="h-full rounded-full bg-amber-500" :style="{ width: d.total > 0 ? `${((integration.decouverte ?? 0) / d.total) * 100}%` : '0%' }"></div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-blue-200 bg-blue-50 p-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-blue-800">En progression</span>
                            <span class="text-sm font-bold text-blue-800">{{ fraction(integration.progression ?? 0, d.total) }}</span>
                        </div>
                        <p class="mt-0.5 text-xs text-blue-600">Formations, integration en cours</p>
                        <div class="mt-2 h-2 rounded-full bg-blue-100">
                            <div class="h-full rounded-full bg-blue-500" :style="{ width: d.total > 0 ? `${((integration.progression ?? 0) / d.total) * 100}%` : '0%' }"></div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-emerald-800">En consolidation</span>
                            <span class="text-sm font-bold text-emerald-800">{{ fraction(integration.consolidation ?? 0, d.total) }}</span>
                        </div>
                        <p class="mt-0.5 text-xs text-emerald-600">Engagement ferme et fidelite</p>
                        <div class="mt-2 h-2 rounded-full bg-emerald-100">
                            <div class="h-full rounded-full bg-emerald-500" :style="{ width: d.total > 0 ? `${((integration.consolidation ?? 0) / d.total) * 100}%` : '0%' }"></div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-violet-200 bg-violet-50 p-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-violet-800">Disciples confirmes</span>
                            <span class="text-sm font-bold text-violet-800">{{ fraction(integration.confirme ?? 0, d.total) }}</span>
                        </div>
                        <p class="mt-0.5 text-xs text-violet-600">Totalement integres a l'eglise et sert</p>
                        <div class="mt-2 h-2 rounded-full bg-violet-100">
                            <div class="h-full rounded-full bg-violet-500" :style="{ width: d.total > 0 ? `${((integration.confirme ?? 0) / d.total) * 100}%` : '0%' }"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══ B. AMES SORTIES DU SUIVI ═══ -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-red-600 text-xs font-bold text-white">B</span>
                    Parmi les ames sorties du suivi : {{ String(d.sorties).padStart(2, '0') }}
                </h2>

                <div v-if="d.sorties > 0" class="mt-4 space-y-3">
                    <div class="flex items-center justify-between rounded-lg bg-red-50 p-3">
                        <span class="text-sm text-red-800">1. Injoignables</span>
                        <span class="text-sm font-bold text-red-800">{{ fraction(d.b_injoignables, d.sorties) }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-orange-50 p-3">
                        <span class="text-sm text-orange-800">2. Ne repond plus</span>
                        <span class="text-sm font-bold text-orange-800">{{ fraction(d.b_ne_repond_plus, d.sorties) }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-amber-50 p-3">
                        <span class="text-sm text-amber-800">3. En deplacement</span>
                        <span class="text-sm font-bold text-amber-800">{{ fraction(d.b_en_deplacement, d.sorties) }}</span>
                    </div>
                    <div v-if="d.b_autre > 0" class="flex items-center justify-between rounded-lg bg-slate-50 p-3">
                        <span class="text-sm text-slate-700">4. Autre motif</span>
                        <span class="text-sm font-bold text-slate-700">{{ fraction(d.b_autre, d.sorties) }}</span>
                    </div>
                </div>
                <div v-else class="mt-3 text-sm text-slate-500">
                    Aucune ame sortie du suivi. Gloire a Dieu !
                </div>

                <!-- Liste noms âmes sorties -->
                <div v-if="amesSorties.length" class="mt-4 border-t border-slate-100 pt-3">
                    <p class="mb-2 text-xs font-medium text-slate-500">Detail :</p>
                    <div class="flex flex-wrap gap-1.5">
                        <span v-for="a in amesSorties" :key="a.id"
                            class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-800 ring-1 ring-red-200">
                            {{ a.prenom }} {{ a.nom }}
                            <span class="ml-1 text-red-500">({{ a.motif_sortie?.replace('_', ' ') || '?' }})</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- ═══ OBSERVATIONS ═══ -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-sm font-bold text-slate-800">Observations</h2>
                <p class="mt-2 text-sm text-slate-600 leading-relaxed whitespace-pre-line">{{ rapport.observations || 'Aucune observation.' }}</p>
            </div>

            <!-- ═══ SIGNATURE ═══ -->
            <div class="rounded-xl bg-gradient-to-r from-amber-50 to-amber-100 p-5 text-center ring-1 ring-amber-200">
                <p class="text-lg font-bold text-amber-800">Gloire a Jesus</p>
                <p class="mt-1 text-xs text-amber-600">Rapport genere le {{ new Date(rapport.updated_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) }}</p>
            </div>

            <!-- Boutons -->
            <div class="flex gap-3 pb-4">
                <Link :href="route('rapport-mensuel.index')" class="flex-1 rounded-lg border border-slate-300 bg-white py-3 text-center text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                    Retour a la liste
                </Link>
                <Link :href="route('rapport-mensuel.create', { mois: rapport.mois })" class="flex-1 rounded-lg bg-blue-600 py-3 text-center text-sm font-semibold text-white transition hover:bg-blue-700">
                    Modifier ce rapport
                </Link>
                <button @click="exportTxt" class="flex-1 rounded-lg border border-slate-300 bg-white py-3 text-center text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    Exporter TXT
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
