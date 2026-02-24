<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import axios from 'axios';

interface Suggestion {
    id: number;
    nom: string;
    prenom: string;
    genre: string | null;
    age: number | null;
    tranche: string | null;
    nb_ames: number;
    cellule_id: number | null;
    score_total: number;
    score_genre: number;
    score_age: number;
    score_charge: number;
    details: { label: string; color: string }[];
}

const props = defineProps<{
    familles: any[];
    cellules: any[];
    familles_impact?: { id: number; nom: string; quartier: string | null }[];
    departements?: { id: number; nom: string }[];
    faiseurs: any[];
    parametres?: Record<string, { id: number; valeur: string; libelle: string; ordre: number }[]>;
    defaultFdId: number | null;
    defaultCelluleId?: number | null;
    userRole: string;
}>();

const fallbackStatut = [
    { valeur: 'NA', libelle: 'Nouveau Arrivant' },
    { valeur: 'NC', libelle: 'Nouveau Converti' },
    { valeur: 'fidele', libelle: 'Fidèle' },
    { valeur: 'STAR', libelle: 'S.T.A.R' },
    { valeur: 'faiseur_disciple', libelle: 'Faiseur de Disciples' },
];
const fallbackSource = [
    { valeur: 'invitation', libelle: 'Invitation' },
    { valeur: 'evangelisation', libelle: 'Évangélisation' },
    { valeur: 'culte', libelle: 'Venu au culte' },
    { valeur: 'autre', libelle: 'Autre' },
];
const optionsStatut = computed(() => props.parametres?.statut_spirituel?.length ? props.parametres.statut_spirituel : fallbackStatut);
const optionsSource = computed(() => props.parametres?.source?.length ? props.parametres.source : fallbackSource);
const optionsProfession = computed(() => props.parametres?.profession ?? []);
const optionsSituation = computed(() => props.parametres?.situation_personnelle ?? []);
const optionsNiveauEtude = computed(() => props.parametres?.niveau_etude ?? []);
const optionsSecteur = computed(() => props.parametres?.secteur_activite ?? []);
const optionsQuartier = computed(() => props.parametres?.quartier ?? []);
const optionsStatutFamilleImpact = computed(() => props.parametres?.statut_famille_impact ?? []);

const form = useForm({
    prenom: '',
    nom: '',
    telephone: '',
    telephone_secondaire: '',
    email: '',
    statut_spirituel: 'NA',
    genre: '',
    date_naissance: '',
    fd_id: props.defaultFdId || props.familles[0]?.id || '',
    cellule_id: props.defaultCelluleId || '',
    famille_impact_id: '' as number | '',
    statut_famille_impact: '',
    en_service_depuis: '' as string,
    departement_id: '' as number | '',
    suivi_par: '',
    quartier: '',
    source: 'invitation',
    notes: '',
    profession: '',
    situation_personnelle: '',
    niveau_etude: '',
    secteur_activite: '',
    nombre_enfants: '' as number | '',
    competences_centres_interet: '',
    contact_urgence_nom: '',
    contact_urgence_telephone: '',
    besoins_particuliers: '',
});

const showFdSelect = props.familles.length > 1;

// ── Detection de doublons en temps reel ──
const doublonExact = ref<any>(null);
const doublonsSimilaires = ref<any[]>([]);
let debounceDoublon: ReturnType<typeof setTimeout> | null = null;

function checkDoublon() {
    if (form.nom.trim().length < 2 || form.prenom.trim().length < 2) {
        doublonExact.value = null;
        doublonsSimilaires.value = [];
        return;
    }
    if (debounceDoublon) clearTimeout(debounceDoublon);
    debounceDoublon = setTimeout(async () => {
        try {
            const { data } = await axios.post(route('membres.checkDoublon'), {
                nom: form.nom.trim(),
                prenom: form.prenom.trim(),
                telephone: form.telephone.trim() || null,
            });
            doublonExact.value = data.exact;
            doublonsSimilaires.value = data.similaires || [];
        } catch { /* silencieux */ }
    }, 500);
}
watch(() => [form.nom, form.prenom, form.telephone], checkDoublon);

const hasExactDoublon = computed(() => !!doublonExact.value);

// ── Suggestion intelligente de faiseur ──
const suggestions = ref<Suggestion[]>([]);
const suggestionLoading = ref(false);
const showSuggestions = ref(false);
let debounceSuggestion: ReturnType<typeof setTimeout> | null = null;

function fetchSuggestions() {
    if (debounceSuggestion) clearTimeout(debounceSuggestion);
    debounceSuggestion = setTimeout(async () => {
        suggestionLoading.value = true;
        try {
            const { data } = await axios.post(route('membres.suggererFaiseur'), {
                fd_id: form.fd_id || null,
                genre: form.genre || null,
                date_naissance: form.date_naissance || null,
            });
            suggestions.value = data.suggestions || [];
            if (suggestions.value.length > 0) {
                showSuggestions.value = true;
            }
        } catch { /* silencieux */ }
        finally { suggestionLoading.value = false; }
    }, 400);
}

// Declencher la suggestion quand genre, date_naissance ou fd_id changent
watch(() => [form.genre, form.date_naissance, form.fd_id], () => {
    if (form.genre || form.date_naissance) {
        fetchSuggestions();
    }
});

function choisirFaiseur(s: Suggestion) {
    form.suivi_par = s.id as any;
    form.cellule_id = s.cellule_id ?? '';
    showSuggestions.value = false;
}

function scoreColor(score: number): string {
    if (score >= 70) return 'text-emerald-700 bg-emerald-50';
    if (score >= 40) return 'text-amber-700 bg-amber-50';
    return 'text-red-700 bg-red-50';
}

function tagColor(color: string): string {
    const map: Record<string, string> = {
        emerald: 'bg-emerald-100 text-emerald-800',
        amber: 'bg-amber-100 text-amber-800',
        red: 'bg-red-100 text-red-800',
    };
    return map[color] || 'bg-slate-100 text-slate-700';
}

// Faiseur selectionne actuellement (pour affichage)
const faiseurSelectionne = computed(() => {
    if (!form.suivi_par) return null;
    // Chercher dans les suggestions d'abord
    const fromSugg = suggestions.value.find(s => s.id === Number(form.suivi_par));
    if (fromSugg) return fromSugg;
    // Sinon dans la liste des faiseurs
    const fromList = props.faiseurs.find(f => f.id === Number(form.suivi_par));
    return fromList ? { ...fromList, score_total: null } : null;
});

function submit() {
    form.post(route('membres.store'));
}
</script>

<template>
    <Head title="Nouveau membre" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('membres.index')" class="text-slate-400 hover:text-slate-600">&larr;</Link>
                <h1 class="text-lg font-semibold text-slate-800">Nouveau membre</h1>
            </div>
        </template>

        <form @submit.prevent="submit" class="mx-auto max-w-2xl space-y-6">
            <!-- Erreur doublon backend -->
            <div v-if="(form.errors as any).doublon" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                <div class="flex items-start gap-3">
                    <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                    </svg>
                    <p>{{ (form.errors as any).doublon }}</p>
                </div>
            </div>

            <!-- Alerte doublon exact -->
            <div v-if="doublonExact" class="rounded-xl border border-red-200 bg-red-50 p-4">
                <div class="flex items-start gap-3">
                    <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.168 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-red-800">Doublon exact detecte !</p>
                        <p class="mt-1 text-sm text-red-700">
                            {{ doublonExact.prenom }} {{ doublonExact.nom }}
                            <span v-if="doublonExact.telephone"> &middot; {{ doublonExact.telephone }}</span>
                            <span v-if="doublonExact.famille_disciples"> &middot; FD : {{ doublonExact.famille_disciples.nom }}</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Alerte similaires -->
            <div v-else-if="doublonsSimilaires.length > 0" class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <div class="flex items-start gap-3">
                    <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.168 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-amber-800">Membres similaires trouves</p>
                        <ul class="mt-1 space-y-1">
                            <li v-for="s in doublonsSimilaires" :key="s.id" class="text-sm text-amber-800">
                                &bull; {{ s.prenom }} {{ s.nom }}
                                <span v-if="s.telephone"> &middot; {{ s.telephone }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- ═══ IDENTITE ═══ -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-slate-500">Identite</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Prenom *</label>
                        <input v-model="form.prenom" type="text" required class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500" />
                        <p v-if="form.errors.prenom" class="mt-1 text-xs text-red-500">{{ form.errors.prenom }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Nom *</label>
                        <input v-model="form.nom" type="text" required class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Téléphone</label>
                        <input v-model="form.telephone" type="tel" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Principal" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Téléphone secondaire</label>
                        <input v-model="form.telephone_secondaire" type="tel" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Optionnel" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Genre</label>
                        <select v-model="form.genre" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm">
                            <option value="">--</option>
                            <option value="M">Homme</option>
                            <option value="F">Femme</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Date de naissance</label>
                        <input v-model="form.date_naissance" type="date" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Quartier</label>
                        <SearchableSelect
                            v-if="optionsQuartier.length"
                            v-model="form.quartier"
                            :options="optionsQuartier.map((p) => ({ valeur: p.valeur, libelle: p.libelle }))"
                            placeholder="Rechercher un quartier..."
                        />
                        <input v-else v-model="form.quartier" type="text" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500" />
                    </div>
                </div>
            </div>

            <!-- ═══ AFFECTATION ═══ -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-slate-500">Affectation</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Statut *</label>
                        <select v-model="form.statut_spirituel" required class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm">
                            <option v-for="p in optionsStatut" :key="p.valeur" :value="p.valeur">{{ p.libelle }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Source</label>
                        <select v-model="form.source" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm">
                            <option value="">—</option>
                            <option v-for="p in optionsSource" :key="p.valeur" :value="p.valeur">{{ p.libelle }}</option>
                        </select>
                    </div>
                    <div v-if="showFdSelect">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Famille de Disciples</label>
                        <select v-model="form.fd_id" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm" required>
                            <option v-for="fd in familles" :key="fd.id" :value="fd.id">{{ fd.nom }}</option>
                        </select>
                    </div>
                    <div v-else>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Famille de Disciples</label>
                        <p class="rounded-lg bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-700">{{ familles[0]?.nom }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Famille d'impact (église de maison)</label>
                        <p class="mb-1 text-xs text-slate-500">Optionnel : connecter l'âme à une famille d'impact.</p>
                        <select v-model="form.famille_impact_id" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm">
                            <option value="">— N'a pas encore intégré</option>
                            <option v-for="fi in (familles_impact || [])" :key="fi.id" :value="fi.id">
                                {{ fi.nom }}{{ fi.quartier ? ' (' + fi.quartier + ')' : '' }}
                            </option>
                        </select>
                    </div>
                    <div v-if="form.famille_impact_id">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Statut par rapport à la famille d'impact</label>
                        <select v-model="form.statut_famille_impact" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm">
                            <option value="">—</option>
                            <option v-for="p in optionsStatutFamilleImpact" :key="p.valeur" :value="p.valeur">{{ p.libelle }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Département / service</label>
                        <p class="mb-1 text-xs text-slate-500">Optionnel : équipe à l'église (accueil, louange, etc.).</p>
                        <select v-model="form.departement_id" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm">
                            <option value="">— Aucun</option>
                            <option v-for="d in (departements || [])" :key="d.id" :value="d.id">{{ d.nom }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">En service depuis</label>
                        <p class="mb-1 text-xs text-slate-500">2 points au faiseur quand l'âme commence le service.</p>
                        <input v-model="form.en_service_depuis" type="date" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500" />
                    </div>
                </div>
            </div>

            <!-- Infos complémentaires (optionnel) -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-slate-500">Infos complémentaires (optionnel)</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Profession</label>
                        <SearchableSelect
                            v-if="optionsProfession.length"
                            v-model="form.profession"
                            :options="optionsProfession.map((p) => ({ valeur: p.valeur, libelle: p.libelle }))"
                            placeholder="Rechercher une profession..."
                        />
                        <input v-else v-model="form.profession" type="text" placeholder="Ex: Enseignant, Infirmier..." class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Situation personnelle</label>
                        <select v-model="form.situation_personnelle" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm">
                            <option value="">—</option>
                            <option v-for="p in optionsSituation" :key="p.valeur" :value="p.valeur">{{ p.libelle }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Niveau d'étude</label>
                        <select v-model="form.niveau_etude" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm">
                            <option value="">—</option>
                            <option v-for="p in optionsNiveauEtude" :key="p.valeur" :value="p.valeur">{{ p.libelle }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Secteur d'activité</label>
                        <SearchableSelect
                            v-model="form.secteur_activite"
                            :options="optionsSecteur"
                            placeholder="Rechercher un secteur..."
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Nombre d'enfants</label>
                        <input v-model.number="form.nombre_enfants" type="number" min="0" max="20" placeholder="0" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Compétences / centres d'intérêt</label>
                        <textarea v-model="form.competences_centres_interet" rows="2" placeholder="Ex: Musique, enseignement, accueil..." class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Contact urgence (nom)</label>
                        <input v-model="form.contact_urgence_nom" type="text" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Contact urgence (téléphone)</label>
                        <input v-model="form.contact_urgence_telephone" type="tel" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Besoins particuliers</label>
                        <textarea v-model="form.besoins_particuliers" rows="2" placeholder="Prières, accompagnement spécifique..." class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    </div>
                </div>
            </div>

            <!-- ═══ AFFECTATION AU FAISEUR (Suggestion IA) ═══ -->
            <div v-if="userRole !== 'faiseur'" class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-500">Affectation au faiseur</h2>
                    <button
                        type="button"
                        @click="fetchSuggestions(); showSuggestions = true"
                        :disabled="suggestionLoading"
                        class="rounded-lg bg-violet-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-violet-700 disabled:opacity-50"
                    >
                        {{ suggestionLoading ? 'Analyse...' : 'Suggestion intelligente' }}
                    </button>
                </div>

                <!-- Faiseur selectionne -->
                <div v-if="faiseurSelectionne" class="mb-3 flex items-center justify-between rounded-lg bg-blue-50 p-3 ring-1 ring-blue-200">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white">
                            {{ faiseurSelectionne.prenom?.[0] }}{{ faiseurSelectionne.nom?.[0] }}
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-blue-800">{{ faiseurSelectionne.prenom }} {{ faiseurSelectionne.nom }}</p>
                            <p v-if="faiseurSelectionne.score_total !== null" class="text-xs text-blue-600">Score : {{ faiseurSelectionne.score_total }}%</p>
                        </div>
                    </div>
                    <button type="button" @click="form.suivi_par = ''" class="text-xs text-blue-500 hover:text-blue-700">Changer</button>
                </div>

                <!-- Panel de suggestions -->
                <div v-if="showSuggestions && suggestions.length > 0" class="space-y-2">
                    <p class="text-xs text-slate-500 mb-2">
                        Classement base sur : genre (40%), tranche d'age (35%), equilibre de charge (25%)
                    </p>
                    <div
                        v-for="(s, idx) in suggestions"
                        :key="s.id"
                        @click="choisirFaiseur(s)"
                        class="flex items-center gap-3 rounded-lg border p-3 cursor-pointer transition hover:shadow-md"
                        :class="String(form.suivi_par) === String(s.id) ? 'border-blue-500 bg-blue-50 ring-1 ring-blue-300' : 'border-slate-200 hover:border-blue-300'"
                    >
                        <!-- Rang -->
                        <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full text-xs font-bold"
                            :class="idx === 0 ? 'bg-emerald-600 text-white' : idx === 1 ? 'bg-blue-500 text-white' : 'bg-slate-200 text-slate-600'">
                            #{{ idx + 1 }}
                        </div>

                        <!-- Info faiseur -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-semibold text-slate-800">{{ s.prenom }} {{ s.nom }}</p>
                                <span class="rounded-full px-2 py-0.5 text-xs font-bold" :class="scoreColor(s.score_total)">
                                    {{ s.score_total }}%
                                </span>
                            </div>

                            <!-- Tags de detail -->
                            <div class="mt-1 flex flex-wrap gap-1">
                                <span
                                    v-for="(tag, tIdx) in s.details"
                                    :key="tIdx"
                                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="tagColor(tag.color)"
                                >{{ tag.label }}</span>
                            </div>

                            <!-- Barres de score -->
                            <div class="mt-2 flex gap-3">
                                <div class="flex-1">
                                    <div class="flex items-center justify-between text-xs text-slate-400 mb-0.5">
                                        <span>Genre</span>
                                        <span>{{ s.score_genre }}%</span>
                                    </div>
                                    <div class="h-1.5 rounded-full bg-slate-100">
                                        <div class="h-full rounded-full transition-all" :class="s.score_genre >= 80 ? 'bg-emerald-500' : s.score_genre >= 30 ? 'bg-amber-500' : 'bg-red-400'" :style="{ width: s.score_genre + '%' }"></div>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between text-xs text-slate-400 mb-0.5">
                                        <span>Age</span>
                                        <span>{{ s.score_age }}%</span>
                                    </div>
                                    <div class="h-1.5 rounded-full bg-slate-100">
                                        <div class="h-full rounded-full transition-all" :class="s.score_age >= 80 ? 'bg-emerald-500' : s.score_age >= 40 ? 'bg-amber-500' : 'bg-red-400'" :style="{ width: s.score_age + '%' }"></div>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between text-xs text-slate-400 mb-0.5">
                                        <span>Charge</span>
                                        <span>{{ s.score_charge }}%</span>
                                    </div>
                                    <div class="h-1.5 rounded-full bg-slate-100">
                                        <div class="h-full rounded-full transition-all" :class="s.score_charge >= 60 ? 'bg-emerald-500' : s.score_charge >= 30 ? 'bg-amber-500' : 'bg-red-400'" :style="{ width: s.score_charge + '%' }"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p v-if="!form.genre && !form.date_naissance" class="text-xs text-amber-600 mt-2">
                        Renseignez le genre et/ou la date de naissance pour un meilleur matching.
                    </p>
                </div>

                <div v-else-if="showSuggestions && suggestions.length === 0 && !suggestionLoading" class="py-4 text-center text-sm text-slate-400">
                    Aucun faiseur disponible dans cette FD.
                </div>

                <!-- Choix manuel -->
                <div class="mt-3">
                    <button type="button" @click="showSuggestions = false" v-if="showSuggestions" class="text-xs text-slate-500 hover:text-slate-700 mb-2">
                        Choix manuel
                    </button>
                    <div v-if="!showSuggestions">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Suivi par (choix manuel)</label>
                        <select v-model="form.suivi_par" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm">
                            <option value="">--</option>
                            <option v-for="f in faiseurs" :key="f.id" :value="f.id">{{ f.prenom }} {{ f.nom }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <label class="mb-1 block text-sm font-medium text-slate-700">Notes</label>
                <textarea v-model="form.notes" rows="3" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
            </div>

            <!-- Submit -->
            <div class="flex gap-3">
                <button
                    type="submit"
                    :disabled="form.processing || hasExactDoublon"
                    class="flex-1 rounded-lg bg-blue-600 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-50"
                >
                    {{ form.processing ? 'Enregistrement...' : (hasExactDoublon ? 'Doublon detecte' : 'Enregistrer le membre') }}
                </button>
                <Link :href="route('membres.index')" class="rounded-lg border border-slate-300 bg-white px-6 py-3 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                    Annuler
                </Link>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
