<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    membre: any;
    familles: any[];
    cellules: any[];
    familles_impact?: { id: number; nom: string; quartier: string | null }[];
    departements?: { id: number; nom: string }[];
    faiseurs: any[];
    parametres?: Record<string, { id: number; valeur: string; libelle: string; ordre: number }[]>;
    userRole?: string;
}>();

const showFdSelect = props.familles.length > 1;

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
    prenom: props.membre.prenom,
    nom: props.membre.nom,
    telephone: props.membre.telephone || '',
    telephone_secondaire: props.membre.telephone_secondaire || '',
    email: props.membre.email || '',
    statut_spirituel: props.membre.statut_spirituel,
    source: props.membre.source || '',
    genre: props.membre.genre || '',
    fd_id: props.membre.fd_id || '',
    cellule_id: props.membre.cellule_id || '',
    famille_impact_id: props.membre.famille_impact_id || '',
    statut_famille_impact: props.membre.statut_famille_impact || '',
    en_service_depuis: props.membre.en_service_depuis || '',
    departement_id: props.membre.departement_id || '',
    suivi_par: props.membre.suivi_par || '',
    quartier: props.membre.quartier || '',
    actif: props.membre.actif,
    notes: props.membre.notes || '',
    profession: props.membre.profession || '',
    situation_personnelle: props.membre.situation_personnelle || '',
    niveau_etude: props.membre.niveau_etude || '',
    secteur_activite: props.membre.secteur_activite || '',
    nombre_enfants: props.membre.nombre_enfants ?? '',
    competences_centres_interet: props.membre.competences_centres_interet || '',
    contact_urgence_nom: props.membre.contact_urgence_nom || '',
    contact_urgence_telephone: props.membre.contact_urgence_telephone || '',
    besoins_particuliers: props.membre.besoins_particuliers || '',
});

function submit() {
    form.put(route('membres.update', props.membre.id));
}
</script>

<template>
    <Head :title="'Modifier ' + membre.prenom" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('membres.show', membre.id)" class="text-slate-400 hover:text-slate-600">&larr;</Link>
                <h1 class="text-lg font-semibold text-slate-800">Modifier {{ membre.prenom }} {{ membre.nom }}</h1>
            </div>
        </template>

        <form @submit.prevent="submit" class="mx-auto max-w-2xl space-y-6">
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-slate-500">Identité</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Prénom</label>
                        <input v-model="form.prenom" type="text" required class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Nom</label>
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
                        <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                        <input v-model="form.email" type="email" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Genre</label>
                        <select v-model="form.genre" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm">
                            <option value="">—</option>
                            <option value="M">Homme</option>
                            <option value="F">Femme</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Quartier</label>
                        <select v-if="optionsQuartier.length" v-model="form.quartier" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm">
                            <option value="">—</option>
                            <option v-for="p in optionsQuartier" :key="p.valeur" :value="p.valeur">{{ p.libelle }}</option>
                        </select>
                        <input v-else v-model="form.quartier" type="text" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500" />
                    </div>
                </div>
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-slate-500">Affectation</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Statut spirituel</label>
                        <select v-model="form.statut_spirituel" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm">
                            <option v-for="p in optionsStatut" :key="p.valeur" :value="p.valeur">{{ p.libelle }}</option>
                        </select>
                    </div>
                    <div v-if="optionsSource.length">
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
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Cellule</label>
                        <select v-model="form.cellule_id" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm">
                            <option value="">—</option>
                            <option v-for="c in cellules" :key="c.id" :value="c.id">{{ c.nom }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Suivi par</label>
                        <select v-model="form.suivi_par" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm">
                            <option value="">—</option>
                            <option v-for="f in faiseurs" :key="f.id" :value="f.id">{{ f.prenom }} {{ f.nom }}</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Famille d'impact (église de maison)</label>
                        <p class="mb-1 text-xs text-slate-500">Connecter l'âme à une famille d'impact pour la communion fraternelle.</p>
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
                        <p class="mb-1 text-xs text-slate-500">Équipe à l'église (accueil, louange, etc.).</p>
                        <select v-model="form.departement_id" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm">
                            <option value="">— Aucun</option>
                            <option v-for="d in (departements || [])" :key="d.id" :value="d.id">{{ d.nom }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">En service depuis</label>
                        <p class="mb-1 text-xs text-slate-500">2 points au faiseur quand l'âme commence le service.</p>
                        <input v-model="form.en_service_depuis" type="date" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500" />
                        <p class="mt-1 text-xs text-slate-500">Laisser vide si l'âme n'est pas encore en service.</p>
                    </div>
                </div>
                <div class="mt-4">
                    <label class="flex items-center gap-3">
                        <input v-model="form.actif" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                        <span class="text-sm font-medium text-slate-700">Membre actif</span>
                    </label>
                </div>
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-slate-500">Infos complémentaires</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Profession</label>
                        <select v-if="optionsProfession.length" v-model="form.profession" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm">
                            <option value="">—</option>
                            <option v-for="p in optionsProfession" :key="p.valeur" :value="p.valeur">{{ p.libelle }}</option>
                        </select>
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
                        <select v-model="form.secteur_activite" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm">
                            <option value="">—</option>
                            <option v-for="p in optionsSecteur" :key="p.valeur" :value="p.valeur">{{ p.libelle }}</option>
                        </select>
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

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <label class="mb-1 block text-sm font-medium text-slate-700">Notes</label>
                <textarea v-model="form.notes" rows="3" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
            </div>

            <div class="flex gap-3">
                <button type="submit" :disabled="form.processing" class="flex-1 rounded-lg bg-blue-600 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-50">
                    {{ form.processing ? 'Enregistrement...' : 'Enregistrer' }}
                </button>
                <Link :href="route('membres.show', membre.id)" class="rounded-lg border border-slate-300 bg-white px-6 py-3 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                    Annuler
                </Link>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
