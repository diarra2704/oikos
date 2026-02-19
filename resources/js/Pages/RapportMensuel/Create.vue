<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

interface Ame {
    id: number;
    nom: string;
    prenom: string;
    telephone: string | null;
    statut_spirituel: string;
    actif: boolean;
    en_pcnc: boolean;
    en_fi: boolean;
    regulier_eglise: boolean;
    niveau_integration: string;
    motif_sortie: string | null;
    date_premiere_visite: string | null;
}

const props = defineProps<{
    ames: Ame[];
    mois: string;
    existant: any;
    faiseurNom: string;
}>();

const form = useForm({
    mois: props.mois,
    observations: props.existant?.observations || '',
    ames: props.ames.map(a => ({
        id: a.id,
        actif: a.actif,
        en_pcnc: a.en_pcnc,
        en_fi: a.en_fi,
        regulier_eglise: a.regulier_eglise,
        niveau_integration: a.niveau_integration || 'decouverte',
        motif_sortie: a.motif_sortie || null,
    })),
});

// Trouver l'âme originale par ID pour l'affichage
function getAme(id: number): Ame | undefined {
    return props.ames.find(a => a.id === id);
}

// Stats en temps réel
const statsLive = computed(() => {
    const total = form.ames.length;
    const suivies = form.ames.filter(a => a.actif);
    const sorties = form.ames.filter(a => !a.actif);
    return {
        total,
        suivies: suivies.length,
        sorties: sorties.length,
        enPcnc: suivies.filter(a => a.en_pcnc).length,
        enFi: suivies.filter(a => a.en_fi).length,
        regulier: suivies.filter(a => a.regulier_eglise).length,
    };
});

// Etape du wizard
const step = ref(1);
const totalSteps = 3;

function nextStep() {
    if (step.value < totalSteps) step.value++;
}
function prevStep() {
    if (step.value > 1) step.value--;
}

function submit() {
    form.post(route('rapport-mensuel.store'));
}

const niveauLabels: Record<string, string> = {
    decouverte: 'Decouverte',
    progression: 'Progression',
    consolidation: 'Consolidation',
    confirme: 'Confirme',
};

const motifLabels: Record<string, string> = {
    injoignable: 'Injoignable',
    ne_repond_plus: 'Ne repond plus',
    en_deplacement: 'En deplacement',
    autre: 'Autre',
};

const moisLabel = computed(() => {
    const [y, m] = props.mois.split('-');
    const months = ['Janvier', 'Fevrier', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Aout', 'Septembre', 'Octobre', 'Novembre', 'Decembre'];
    return `${months[parseInt(m) - 1]} ${y}`;
});
</script>

<template>
    <Head title="Rapport mensuel - Saisie" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('rapport-mensuel.index')" class="text-slate-400 hover:text-slate-600">&larr;</Link>
                <h1 class="text-lg font-semibold text-slate-800">Rapport mensuel</h1>
            </div>
        </template>

        <div class="mx-auto max-w-3xl space-y-6">
            <!-- En-tête -->
            <div class="rounded-xl bg-gradient-to-r from-blue-600 to-blue-700 p-5 text-white shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wider opacity-80">Rapport Synthetique de Suivi</p>
                <p class="mt-1 text-lg font-bold">{{ moisLabel }}</p>
                <p class="text-sm opacity-90">Faiseur : {{ faiseurNom }}</p>
                <div v-if="existant" class="mt-2 rounded-lg bg-white/20 px-3 py-1.5 text-xs">
                    Un rapport existe deja pour ce mois. Il sera mis a jour.
                </div>
            </div>

            <!-- Stats en direct -->
            <div class="grid grid-cols-3 gap-3 sm:grid-cols-6">
                <div class="rounded-lg bg-white p-3 text-center shadow-sm ring-1 ring-slate-200">
                    <p class="text-2xl font-bold text-slate-800">{{ statsLive.total }}</p>
                    <p class="text-xs text-slate-500">Total</p>
                </div>
                <div class="rounded-lg bg-white p-3 text-center shadow-sm ring-1 ring-slate-200">
                    <p class="text-2xl font-bold text-emerald-600">{{ statsLive.suivies }}</p>
                    <p class="text-xs text-slate-500">Suivies</p>
                </div>
                <div class="rounded-lg bg-white p-3 text-center shadow-sm ring-1 ring-slate-200">
                    <p class="text-2xl font-bold text-red-600">{{ statsLive.sorties }}</p>
                    <p class="text-xs text-slate-500">Sorties</p>
                </div>
                <div class="rounded-lg bg-white p-3 text-center shadow-sm ring-1 ring-slate-200">
                    <p class="text-2xl font-bold text-purple-600">{{ statsLive.enPcnc }}</p>
                    <p class="text-xs text-slate-500">PCNC</p>
                </div>
                <div class="rounded-lg bg-white p-3 text-center shadow-sm ring-1 ring-slate-200">
                    <p class="text-2xl font-bold text-indigo-600">{{ statsLive.enFi }}</p>
                    <p class="text-xs text-slate-500">FI</p>
                </div>
                <div class="rounded-lg bg-white p-3 text-center shadow-sm ring-1 ring-slate-200">
                    <p class="text-2xl font-bold text-cyan-600">{{ statsLive.regulier }}</p>
                    <p class="text-xs text-slate-500">Reguliers</p>
                </div>
            </div>

            <!-- Indicateur d'étape -->
            <div class="flex items-center justify-center gap-2">
                <div v-for="s in totalSteps" :key="s" class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold transition"
                        :class="s === step ? 'bg-blue-600 text-white' : s < step ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-400'">
                        {{ s }}
                    </div>
                    <div v-if="s < totalSteps" class="h-0.5 w-8 rounded" :class="s < step ? 'bg-emerald-300' : 'bg-slate-200'"></div>
                </div>
            </div>

            <!-- ═══ ETAPE 1 : Statut actif/sorti pour chaque âme ═══ -->
            <div v-if="step === 1" class="space-y-3">
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                    <h2 class="mb-1 text-sm font-semibold text-slate-800">Etape 1 : Statut de suivi</h2>
                    <p class="text-xs text-slate-500">Pour chaque ame, indiquez si elle est encore suivie ou sortie du suivi.</p>
                </div>

                <div v-for="(ameForm, idx) in form.ames" :key="ameForm.id" class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-full text-xs font-bold"
                                :class="ameForm.actif ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'">
                                {{ getAme(ameForm.id)?.prenom?.[0] }}{{ getAme(ameForm.id)?.nom?.[0] }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-800">{{ getAme(ameForm.id)?.prenom }} {{ getAme(ameForm.id)?.nom }}</p>
                                <p class="text-xs text-slate-500">{{ getAme(ameForm.id)?.statut_spirituel }} <span v-if="getAme(ameForm.id)?.telephone">&middot; {{ getAme(ameForm.id)?.telephone }}</span></p>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <button
                                type="button"
                                @click="ameForm.actif = true; ameForm.motif_sortie = null"
                                class="rounded-lg px-3 py-1.5 text-xs font-medium transition"
                                :class="ameForm.actif ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            >Suivi</button>
                            <button
                                type="button"
                                @click="ameForm.actif = false"
                                class="rounded-lg px-3 py-1.5 text-xs font-medium transition"
                                :class="!ameForm.actif ? 'bg-red-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            >Sorti(e)</button>
                        </div>
                    </div>

                    <!-- Motif de sortie si inactif -->
                    <div v-if="!ameForm.actif" class="mt-3 rounded-lg bg-red-50 p-3">
                        <label class="mb-1 block text-xs font-medium text-red-700">Motif de sortie</label>
                        <select v-model="ameForm.motif_sortie" class="w-full rounded-lg border border-red-200 bg-white px-3 py-2 text-sm focus:border-red-400 focus:ring-red-400">
                            <option :value="null">-- Selectionner --</option>
                            <option v-for="(label, key) in motifLabels" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- ═══ ETAPE 2 : Détails pour les âmes suivies ═══ -->
            <div v-if="step === 2" class="space-y-3">
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                    <h2 class="mb-1 text-sm font-semibold text-slate-800">Etape 2 : Details du suivi</h2>
                    <p class="text-xs text-slate-500">Pour chaque ame encore suivie, renseignez son parcours.</p>
                </div>

                <div v-for="ameForm in form.ames.filter(a => a.actif)" :key="'detail-'+ameForm.id"
                    class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                    <p class="mb-3 text-sm font-semibold text-slate-800">{{ getAme(ameForm.id)?.prenom }} {{ getAme(ameForm.id)?.nom }}</p>

                    <!-- Toggles -->
                    <div class="space-y-3">
                        <label class="flex items-center justify-between">
                            <span class="text-sm text-slate-700">Dans le PCNC ?</span>
                            <button type="button" @click="ameForm.en_pcnc = !ameForm.en_pcnc"
                                class="relative h-6 w-11 rounded-full transition"
                                :class="ameForm.en_pcnc ? 'bg-purple-600' : 'bg-slate-300'">
                                <span class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform"
                                    :class="ameForm.en_pcnc ? 'translate-x-5' : ''"></span>
                            </button>
                        </label>
                        <label class="flex items-center justify-between">
                            <span class="text-sm text-slate-700">Dans une FI ?</span>
                            <button type="button" @click="ameForm.en_fi = !ameForm.en_fi"
                                class="relative h-6 w-11 rounded-full transition"
                                :class="ameForm.en_fi ? 'bg-indigo-600' : 'bg-slate-300'">
                                <span class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform"
                                    :class="ameForm.en_fi ? 'translate-x-5' : ''"></span>
                            </button>
                        </label>
                        <label class="flex items-center justify-between">
                            <span class="text-sm text-slate-700">Regulier a l'eglise ?</span>
                            <button type="button" @click="ameForm.regulier_eglise = !ameForm.regulier_eglise"
                                class="relative h-6 w-11 rounded-full transition"
                                :class="ameForm.regulier_eglise ? 'bg-cyan-600' : 'bg-slate-300'">
                                <span class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform"
                                    :class="ameForm.regulier_eglise ? 'translate-x-5' : ''"></span>
                            </button>
                        </label>
                    </div>

                    <!-- Niveau d'intégration -->
                    <div class="mt-4">
                        <label class="mb-2 block text-xs font-medium text-slate-600">Niveau d'integration</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" v-for="(label, key) in niveauLabels" :key="key"
                                @click="ameForm.niveau_integration = key"
                                class="rounded-lg border px-3 py-2 text-xs font-medium transition"
                                :class="ameForm.niveau_integration === key
                                    ? 'border-blue-500 bg-blue-50 text-blue-700'
                                    : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'">
                                {{ label }}
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="form.ames.filter(a => a.actif).length === 0"
                    class="rounded-xl bg-slate-50 p-8 text-center text-sm text-slate-400">
                    Aucune ame en suivi actif.
                </div>
            </div>

            <!-- ═══ ETAPE 3 : Observations + Confirmation ═══ -->
            <div v-if="step === 3" class="space-y-4">
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                    <h2 class="mb-1 text-sm font-semibold text-slate-800">Etape 3 : Observations et validation</h2>
                    <p class="text-xs text-slate-500">Ajoutez vos observations et generez le rapport.</p>
                </div>

                <!-- Résumé rapide -->
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <h3 class="mb-3 text-sm font-semibold text-slate-700">Resume</h3>
                    <div class="space-y-2 text-sm text-slate-600">
                        <p>Total ames recues : <span class="font-bold text-slate-800">{{ statsLive.total }}</span></p>
                        <p class="ml-4">A- Ames encore suivies : <span class="font-bold text-emerald-700">{{ statsLive.suivies }}/{{ statsLive.total }}</span></p>
                        <p class="ml-4">B- Ames sorties du suivi : <span class="font-bold text-red-700">{{ statsLive.sorties }}/{{ statsLive.total }}</span></p>
                        <div class="mt-2 border-t border-slate-100 pt-2">
                            <p class="ml-4">En PCNC : <span class="font-bold">{{ statsLive.enPcnc }}/{{ statsLive.suivies }}</span></p>
                            <p class="ml-4">En FI : <span class="font-bold">{{ statsLive.enFi }}/{{ statsLive.suivies }}</span></p>
                            <p class="ml-4">Reguliers a l'eglise : <span class="font-bold">{{ statsLive.regulier }}/{{ statsLive.suivies }}</span></p>
                        </div>
                    </div>
                </div>

                <!-- Observations -->
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <label class="mb-2 block text-sm font-medium text-slate-700">Observations</label>
                    <textarea
                        v-model="form.observations"
                        rows="4"
                        placeholder="Les principaux axes d'amelioration concernent..."
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                    ></textarea>
                </div>
            </div>

            <!-- Navigation wizard -->
            <div class="flex gap-3">
                <button v-if="step > 1" @click="prevStep" type="button"
                    class="rounded-lg border border-slate-300 bg-white px-6 py-3 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                    Precedent
                </button>
                <button v-if="step < totalSteps" @click="nextStep" type="button"
                    class="flex-1 rounded-lg bg-blue-600 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
                    Suivant
                </button>
                <button v-if="step === totalSteps" @click="submit" type="button"
                    :disabled="form.processing"
                    class="flex-1 rounded-lg bg-emerald-600 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-50">
                    {{ form.processing ? 'Generation...' : 'Generer le rapport' }}
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
