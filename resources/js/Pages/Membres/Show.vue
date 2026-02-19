<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps<{
    membre: any;
    parametres?: Record<string, { id: number; valeur: string; libelle: string; ordre: number }[]>;
    userRole?: string;
    canAddInteraction?: boolean;
    canAddRappel?: boolean;
    canDeleteMembre?: boolean;
    canEditFormations?: boolean;
}>();

function labelFromParametres(type: string, valeur: string | null | undefined): string {
    if (!valeur || !props.parametres?.[type]) return '';
    const p = props.parametres[type].find((x: any) => x.valeur === valeur);
    return p ? p.libelle : '';
}

const confirmDelete = ref(false);
const user = usePage().props.auth.user as any;

const statutLabels: Record<string, string> = {
    NA: 'Nouveau Arrivant',
    NC: 'Nouveau Converti',
    fidele: 'Fidele',
    STAR: 'S.T.A.R',
    faiseur_disciple: 'Faiseur de Disciples',
};

const statutColors: Record<string, string> = {
    NA: 'bg-amber-100 text-amber-700 ring-amber-200',
    NC: 'bg-blue-100 text-blue-700 ring-blue-200',
    fidele: 'bg-emerald-100 text-emerald-700 ring-emerald-200',
    STAR: 'bg-purple-100 text-purple-700 ring-purple-200',
    faiseur_disciple: 'bg-indigo-100 text-indigo-700 ring-indigo-200',
};

const canalIcons: Record<string, { icon: string; label: string; color: string }> = {
    appel: { icon: '📞', label: 'Appel telephonique', color: 'bg-green-100 text-green-700 ring-green-200' },
    whatsapp: { icon: '💬', label: 'WhatsApp', color: 'bg-emerald-100 text-emerald-700 ring-emerald-200' },
    visite: { icon: '🏠', label: 'Visite physique', color: 'bg-blue-100 text-blue-700 ring-blue-200' },
    sms: { icon: '📱', label: 'SMS', color: 'bg-sky-100 text-sky-700 ring-sky-200' },
    rencontre_eglise: { icon: '⛪', label: 'Rencontre a l\'eglise', color: 'bg-purple-100 text-purple-700 ring-purple-200' },
    autre: { icon: '📌', label: 'Autre', color: 'bg-slate-100 text-slate-700 ring-slate-200' },
};

function deleteMembre() {
    router.delete(route('membres.destroy', props.membre.id));
}

// ── Formulaire d'ajout d'interaction ──
const showInteractionForm = ref(false);
const interactionForm = useForm({
    type_canal: 'appel',
    date_interaction: new Date().toISOString().slice(0, 16),
    resume: '',
    duree_minutes: null as number | null,
    prochain_rdv: '',
    prochain_objectif: '',
});

function submitInteraction() {
    interactionForm.post(route('interactions.store', props.membre.id), {
        onSuccess: () => {
            showInteractionForm.value = false;
            interactionForm.reset();
            interactionForm.date_interaction = new Date().toISOString().slice(0, 16);
        },
    });
}

function deleteInteraction(id: number) {
    if (confirm('Supprimer cette interaction ?')) {
        router.delete(route('interactions.destroy', id));
    }
}

// ── Rappels ──
const showRappelForm = ref(false);
const tomorrow = (() => { const d = new Date(); d.setDate(d.getDate() + 1); return d.toISOString().slice(0, 10); })();
const rappelForm = useForm({
    membre_id: props.membre.id,
    type: 'contacter',
    date_souhaitee: tomorrow,
    libelle: '',
});
const rappelTypeLabels: Record<string, string> = {
    contacter: 'Contacter',
    relance_interaction: 'Relance interaction',
};

function submitRappel() {
    rappelForm.post(route('rappels.store'), {
        onSuccess: () => {
            showRappelForm.value = false;
            rappelForm.reset();
            rappelForm.membre_id = props.membre.id;
            rappelForm.type = 'contacter';
            rappelForm.date_souhaitee = (() => { const d = new Date(); d.setDate(d.getDate() + 1); return d.toISOString().slice(0, 10); })();
            rappelForm.libelle = '';
        },
    });
}

function marquerRappelFait(rappelId: number) {
    router.put(route('rappels.fait', rappelId));
}

function deleteRappel(rappelId: number) {
    if (confirm('Supprimer ce rappel ?')) {
        router.delete(route('rappels.destroy', rappelId));
    }
}

// Prochain RDV le plus récent
const prochainRdv = computed(() => {
    const interactions = props.membre.interactions || [];
    const avecRdv = interactions.filter((i: any) => i.prochain_rdv);
    if (!avecRdv.length) return null;
    return avecRdv.sort((a: any, b: any) => b.date_interaction.localeCompare(a.date_interaction))[0];
});

// Stats rapides
const statsInteractions = computed(() => {
    const list = props.membre.interactions || [];
    return {
        total: list.length,
        ceMois: list.filter((i: any) => {
            const d = new Date(i.date_interaction);
            const now = new Date();
            return d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear();
        }).length,
        derniere: list.length > 0 ? list[0] : null,
    };
});

function formatDate(dateStr: string): string {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });
}

function formatDateTime(dateStr: string): string {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

const situationLabels: Record<string, string> = {
    marie_sans_enfant: 'Marié(e) sans enfant',
    marie_avec_enfant: 'Marié(e) avec enfant',
    fiance: 'Fiancé(e)',
    celibataire_sans_enfant: 'Célibataire sans enfant',
    celibataire_avec_enfant: 'Célibataire avec enfant',
    veuf: 'Veuf / Veuve',
    divorce: 'Divorcé(e)',
};
const niveauEtudeLabels: Record<string, string> = {
    primaire: 'Primaire',
    secondaire: 'Secondaire',
    bac: 'Bac',
    licence: 'Licence',
    master: 'Master',
    doctorat: 'Doctorat',
    autre: 'Autre',
};
const secteurLabels: Record<string, string> = {
    sante: 'Santé',
    enseignement: 'Enseignement',
    commerce: 'Commerce',
    administration: 'Administration',
    technique: 'Technique / Ingénierie',
    artisanat: 'Artisanat',
    sans_emploi: 'Sans emploi',
    etudiant: 'Étudiant',
    retraite: 'Retraité',
    autre: 'Autre',
};
function labelSituation(v: string | null | undefined): string {
    return (v && situationLabels[v]) ? situationLabels[v] : '---';
}
function labelNiveauEtude(v: string | null | undefined): string {
    return (v && niveauEtudeLabels[v]) ? niveauEtudeLabels[v] : '---';
}
function labelSecteur(v: string | null | undefined): string {
    return (v && secteurLabels[v]) ? secteurLabels[v] : '---';
}

// ── Parcours de formation ──
const formationStatuts = ref<Record<string, string>>({});
watch(
    () => [props.membre?.formations, props.parametres?.type_formation],
    () => {
        const next: Record<string, string> = {};
        const types = props.parametres?.type_formation ?? [];
        const forms = props.membre?.formations ?? [];
        for (const t of types) {
            next[t.valeur] = forms.find((f: any) => f.type_formation === t.valeur)?.statut_formation ?? '';
        }
        formationStatuts.value = next;
    },
    { immediate: true }
);

const savingFormations = ref(false);
function submitFormations() {
    const formations = Object.entries(formationStatuts.value)
        .filter(([, s]) => s)
        .map(([type_formation, statut_formation]) => ({ type_formation, statut_formation }));
    savingFormations.value = true;
    router.put(route('membres.formations.update', props.membre.id), { formations }, {
        onFinish: () => { savingFormations.value = false; },
    });
}
</script>

<template>
    <Head :title="membre.prenom + ' ' + membre.nom" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('membres.index')" class="text-slate-400 hover:text-slate-600">&larr;</Link>
                <h1 class="text-lg font-semibold text-slate-800">{{ membre.prenom }} {{ membre.nom }}</h1>
            </div>
        </template>

        <div class="mx-auto max-w-2xl space-y-5">
            <!-- En-tete profil -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full text-lg font-bold" :class="statutColors[membre.statut_spirituel] || 'bg-slate-100 text-slate-600'">
                            {{ membre.prenom?.[0] }}{{ membre.nom?.[0] }}
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-slate-800">{{ membre.prenom }} {{ membre.nom }}</h2>
                            <span class="mt-1 inline-block rounded-full px-3 py-1 text-xs font-semibold ring-1" :class="statutColors[membre.statut_spirituel] || 'bg-slate-100 text-slate-600 ring-slate-200'">
                                {{ statutLabels[membre.statut_spirituel] || membre.statut_spirituel }}
                            </span>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Link v-if="['admin', 'superviseur'].includes(user?.role)" :href="route('transferts.create')" class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-700 transition hover:bg-amber-100">
                            Transferer
                        </Link>
                        <Link :href="route('membres.edit', membre.id)" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                            Modifier
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Informations -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-slate-500">Informations</h3>
                <dl class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs text-slate-500">Telephone</dt>
                        <dd class="text-sm font-medium text-slate-800">{{ membre.telephone || '---' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Email</dt>
                        <dd class="text-sm font-medium text-slate-800">{{ membre.email || '---' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Genre</dt>
                        <dd class="text-sm font-medium text-slate-800">{{ membre.genre === 'M' ? 'Homme' : membre.genre === 'F' ? 'Femme' : '---' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Quartier</dt>
                        <dd class="text-sm font-medium text-slate-800">{{ membre.quartier || '---' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Famille de Disciples</dt>
                        <dd class="text-sm font-medium text-slate-800">{{ membre.famille_disciples?.nom || '---' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Cellule</dt>
                        <dd class="text-sm font-medium text-slate-800">{{ membre.cellule?.nom || '---' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Suivi par</dt>
                        <dd class="text-sm font-medium text-slate-800">{{ membre.faiseur?.prenom || '---' }} {{ membre.faiseur?.nom || '' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Derniere presence</dt>
                        <dd class="text-sm font-medium text-slate-800">{{ membre.derniere_presence ? formatDate(membre.derniere_presence) : '---' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Famille d'impact (église de maison)</dt>
                        <dd class="text-sm font-medium text-slate-800">
                            {{ membre.famille_impact?.nom ?? '---' }}
                            <span v-if="membre.famille_impact?.quartier" class="text-slate-500"> ({{ membre.famille_impact.quartier }})</span>
                        </dd>
                    </div>
                    <div v-if="membre.famille_impact_id">
                        <dt class="text-xs text-slate-500">Statut famille d'impact</dt>
                        <dd class="text-sm font-medium text-slate-800">{{ labelFromParametres('statut_famille_impact', membre.statut_famille_impact) || membre.statut_famille_impact || '---' }}</dd>
                    </div>
                    <div v-if="membre.departement">
                        <dt class="text-xs text-slate-500">Département / service</dt>
                        <dd class="text-sm font-medium text-slate-800">{{ membre.departement.nom }}</dd>
                    </div>
                    <div v-if="membre.en_service_depuis">
                        <dt class="text-xs text-slate-500">En service depuis</dt>
                        <dd class="text-sm font-medium text-slate-800">{{ formatDate(membre.en_service_depuis) }}</dd>
                    </div>
                    <template v-if="membre.created_by || membre.updated_by">
                        <div>
                            <dt class="text-xs text-slate-500">Créé par</dt>
                            <dd class="text-sm font-medium text-slate-800">{{ membre.created_by ? (membre.created_by.prenom + ' ' + membre.created_by.nom) : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">Modifié par</dt>
                            <dd class="text-sm font-medium text-slate-800">{{ membre.updated_by ? (membre.updated_by.prenom + ' ' + membre.updated_by.nom) : '—' }}</dd>
                        </div>
                    </template>
                </dl>
            </div>

            <!-- Infos complementaires -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-slate-500">Infos complementaires</h3>
                <dl class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs text-slate-500">Profession</dt>
                        <dd class="text-sm font-medium text-slate-800">{{ membre.profession || '---' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Situation personnelle</dt>
                        <dd class="text-sm font-medium text-slate-800">{{ labelFromParametres('situation_personnelle', membre.situation_personnelle) || labelSituation(membre.situation_personnelle) || '---' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Niveau d'etude</dt>
                        <dd class="text-sm font-medium text-slate-800">{{ labelFromParametres('niveau_etude', membre.niveau_etude) || labelNiveauEtude(membre.niveau_etude) || '---' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Secteur d'activite</dt>
                        <dd class="text-sm font-medium text-slate-800">{{ labelFromParametres('secteur_activite', membre.secteur_activite) || labelSecteur(membre.secteur_activite) || '---' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Nombre d'enfants</dt>
                        <dd class="text-sm font-medium text-slate-800">{{ membre.nombre_enfants != null ? membre.nombre_enfants : '---' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs text-slate-500">Competences / centres d'interet</dt>
                        <dd class="mt-0.5 text-sm font-medium text-slate-800 whitespace-pre-line">{{ membre.competences_centres_interet || '---' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Contact urgence (nom)</dt>
                        <dd class="text-sm font-medium text-slate-800">{{ membre.contact_urgence_nom || '---' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Contact urgence (telephone)</dt>
                        <dd class="text-sm font-medium text-slate-800">{{ membre.contact_urgence_telephone || '---' }}</dd>
                    </div>
                    <div class="sm:col-span-2" v-if="membre.besoins_particuliers">
                        <dt class="text-xs text-slate-500">Besoins particuliers</dt>
                        <dd class="mt-0.5 text-sm font-medium text-slate-800 whitespace-pre-line">{{ membre.besoins_particuliers }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Parcours de formation -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-slate-500">Parcours de formation</h3>
                <div v-if="parametres?.type_formation?.length" class="space-y-3">
                    <div
                        v-for="typeF in parametres.type_formation"
                        :key="typeF.valeur"
                        class="flex flex-wrap items-center gap-3 rounded-lg border border-slate-100 bg-slate-50/50 px-3 py-2"
                    >
                        <span class="min-w-[200px] text-sm font-medium text-slate-800 sm:min-w-0">{{ typeF.libelle }}</span>
                        <template v-if="canEditFormations">
                            <select
                                v-model="formationStatuts[typeF.valeur]"
                                class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">— Non renseigné</option>
                                <option
                                    v-for="s in (parametres.statut_formation || [])"
                                    :key="s.valeur"
                                    :value="s.valeur"
                                >
                                    {{ s.libelle }}
                                </option>
                            </select>
                        </template>
                        <template v-else>
                            <span class="text-sm text-slate-600">
                                {{ labelFromParametres('statut_formation', (membre.formations || []).find((f: any) => f.type_formation === typeF.valeur)?.statut_formation) || '—' }}
                            </span>
                        </template>
                    </div>
                    <div v-if="canEditFormations" class="pt-2">
                        <button
                            @click="submitFormations"
                            :disabled="savingFormations"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{ savingFormations ? 'Enregistrement...' : 'Enregistrer le parcours' }}
                        </button>
                    </div>
                </div>
                <p v-else class="text-sm text-slate-400">Aucun type de formation configuré (paramétrage).</p>
            </div>

            <!-- ═══ INTERACTIONS ═══ -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500">Interactions</h3>
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-slate-400">{{ statsInteractions.total }} total &middot; {{ statsInteractions.ceMois }} ce mois</span>
                        <button
                            v-if="canAddInteraction"
                            @click="showInteractionForm = !showInteractionForm"
                            class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-blue-700"
                        >
                            {{ showInteractionForm ? 'Annuler' : '+ Interaction' }}
                        </button>
                    </div>
                </div>

                <!-- Prochain RDV -->
                <div v-if="prochainRdv" class="mt-3 rounded-lg bg-amber-50 p-3 ring-1 ring-amber-200">
                    <p class="text-xs font-medium text-amber-800">
                        Prochain contact prevu : <span class="font-bold">{{ formatDate(prochainRdv.prochain_rdv) }}</span>
                    </p>
                    <p v-if="prochainRdv.prochain_objectif" class="mt-0.5 text-xs text-amber-700">{{ prochainRdv.prochain_objectif }}</p>
                </div>

                <!-- Formulaire d'ajout rapide -->
                <div v-if="showInteractionForm" class="mt-4 rounded-xl border border-blue-200 bg-blue-50/50 p-4">
                    <h4 class="mb-3 text-sm font-semibold text-blue-800">Nouvelle interaction</h4>
                    <div class="space-y-3">
                        <!-- Type de canal -->
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-700">Type de contact</label>
                            <div class="grid grid-cols-3 gap-2 sm:grid-cols-6">
                                <button
                                    v-for="(info, key) in canalIcons" :key="key"
                                    type="button"
                                    @click="interactionForm.type_canal = key"
                                    class="flex flex-col items-center gap-1 rounded-lg border p-2 text-xs font-medium transition"
                                    :class="interactionForm.type_canal === key
                                        ? 'border-blue-500 bg-blue-100 text-blue-800'
                                        : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'"
                                >
                                    <span class="text-lg">{{ info.icon }}</span>
                                    <span class="text-center leading-tight">{{ info.label.split(' ')[0] }}</span>
                                </button>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-700">Date et heure</label>
                                <input v-model="interactionForm.date_interaction" type="datetime-local" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-700">Duree (minutes)</label>
                                <input v-model.number="interactionForm.duree_minutes" type="number" min="1" placeholder="Ex: 15" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-700">Resume de l'echange *</label>
                            <textarea v-model="interactionForm.resume" rows="3" required placeholder="Qu'est-ce qui a ete dit/fait ?" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                            <p v-if="interactionForm.errors.resume" class="mt-1 text-xs text-red-500">{{ interactionForm.errors.resume }}</p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-700">Prochain RDV</label>
                                <input v-model="interactionForm.prochain_rdv" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-700">Objectif prochain contact</label>
                                <input v-model="interactionForm.prochain_objectif" type="text" placeholder="Ex: Inviter au culte" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                        </div>

                        <button
                            @click="submitInteraction"
                            :disabled="interactionForm.processing || !interactionForm.resume"
                            class="w-full rounded-lg bg-blue-600 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{ interactionForm.processing ? 'Enregistrement...' : 'Enregistrer l\'interaction' }}
                        </button>
                    </div>
                </div>

                <!-- Timeline des interactions -->
                <div class="mt-4">
                    <div v-if="membre.interactions?.length" class="relative">
                        <!-- Ligne verticale -->
                        <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-slate-200"></div>

                        <div v-for="(inter, idx) in membre.interactions" :key="inter.id" class="relative mb-4 pl-10">
                            <!-- Point sur la timeline -->
                            <div class="absolute left-2 top-1.5 flex h-5 w-5 items-center justify-center rounded-full ring-2 ring-white"
                                :class="canalIcons[inter.type_canal]?.color || 'bg-slate-100 text-slate-600'">
                                <span class="text-xs">{{ canalIcons[inter.type_canal]?.icon || '?' }}</span>
                            </div>

                            <div class="rounded-lg border border-slate-100 bg-slate-50/50 p-3 transition hover:bg-white">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ring-1"
                                            :class="canalIcons[inter.type_canal]?.color || 'bg-slate-100 text-slate-600 ring-slate-200'">
                                            {{ canalIcons[inter.type_canal]?.label || inter.type_canal }}
                                        </span>
                                        <span class="ml-2 text-xs text-slate-400">{{ formatDateTime(inter.date_interaction) }}</span>
                                        <span v-if="inter.duree_minutes" class="ml-1 text-xs text-slate-400">&middot; {{ inter.duree_minutes }} min</span>
                                    </div>
                                    <button
                                        v-if="inter.faiseur_id === user?.id || user?.role === 'admin'"
                                        @click="deleteInteraction(inter.id)"
                                        class="text-xs text-slate-300 transition hover:text-red-500"
                                        title="Supprimer"
                                    >&times;</button>
                                </div>

                                <p class="mt-2 text-sm text-slate-700 whitespace-pre-line">{{ inter.resume }}</p>

                                <div v-if="inter.faiseur" class="mt-2 text-xs text-slate-400">
                                    Par {{ inter.faiseur.prenom }} {{ inter.faiseur.nom }}
                                </div>

                                <div v-if="inter.prochain_rdv" class="mt-2 rounded bg-amber-50 px-2 py-1 text-xs text-amber-700 ring-1 ring-amber-200">
                                    Prochain contact : {{ formatDate(inter.prochain_rdv) }}
                                    <span v-if="inter.prochain_objectif"> &mdash; {{ inter.prochain_objectif }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="py-6 text-center text-sm text-slate-400">
                        Aucune interaction enregistree.
                        <span v-if="canAddInteraction"> Cliquez sur "+ Interaction" pour en ajouter une.</span>
                    </div>
                </div>
            </div>

            <!-- Rappels -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500">Rappels</h3>
                    <button
                        v-if="canAddRappel"
                        @click="showRappelForm = !showRappelForm"
                        class="rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-amber-700"
                    >
                        {{ showRappelForm ? 'Annuler' : '+ Rappel' }}
                    </button>
                </div>

                <div v-if="showRappelForm" class="mt-4 rounded-xl border border-amber-200 bg-amber-50/50 p-4">
                    <h4 class="mb-3 text-sm font-semibold text-amber-800">Nouveau rappel</h4>
                    <div class="space-y-3">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-700">Type</label>
                            <select v-model="rappelForm.type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-amber-500">
                                <option value="contacter">Contacter</option>
                                <option value="relance_interaction">Relance interaction</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-700">Date souhaitée</label>
                            <input v-model="rappelForm.date_souhaitee" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-amber-500" />
                            <p v-if="rappelForm.errors.date_souhaitee" class="mt-1 text-xs text-red-500">{{ rappelForm.errors.date_souhaitee }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-700">Libellé (optionnel)</label>
                            <input v-model="rappelForm.libelle" type="text" placeholder="Ex: Rappeler pour invitation culte" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-amber-500" />
                        </div>
                        <button
                            @click="submitRappel"
                            :disabled="rappelForm.processing || !rappelForm.date_souhaitee"
                            class="w-full rounded-lg bg-amber-600 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-700 disabled:opacity-50"
                        >
                            {{ rappelForm.processing ? 'Enregistrement...' : 'Créer le rappel' }}
                        </button>
                    </div>
                </div>

                <ul v-if="membre.rappels?.length" class="mt-4 space-y-2">
                    <li v-for="r in membre.rappels" :key="r.id" class="flex items-center justify-between rounded-lg border border-amber-100 bg-amber-50/50 px-3 py-2">
                        <div>
                            <span class="font-medium text-slate-800">{{ rappelTypeLabels[r.type] || r.type }}</span>
                            <span class="ml-2 text-sm text-slate-500">— {{ formatDate(r.date_souhaitee) }}</span>
                            <p v-if="r.libelle" class="mt-0.5 text-xs text-slate-600">{{ r.libelle }}</p>
                        </div>
                        <div class="flex gap-2">
                            <button @click="marquerRappelFait(r.id)" class="rounded px-2 py-1 text-xs font-medium text-emerald-600 hover:bg-emerald-100" title="Marquer comme fait">✓ Fait</button>
                            <button @click="deleteRappel(r.id)" class="rounded px-2 py-1 text-xs text-slate-400 hover:bg-slate-200 hover:text-red-600" title="Supprimer">×</button>
                        </div>
                    </li>
                </ul>
                <p v-else-if="!showRappelForm" class="mt-4 text-sm text-slate-400">Aucun rappel en attente.</p>
            </div>

            <!-- Historique presences -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wider text-slate-500">Presences recentes</h3>
                <div v-if="membre.presences?.length" class="space-y-2">
                    <div v-for="p in membre.presences" :key="p.id" class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2">
                        <span class="text-sm text-slate-600">{{ p.date_evenement }} -- {{ p.type_evenement }}</span>
                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="p.present ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'">
                            {{ p.present ? 'Present' : 'Absent' }}
                        </span>
                    </div>
                </div>
                <p v-else class="text-sm text-slate-400">Aucune presence enregistree.</p>
            </div>

            <!-- Notes -->
            <div v-if="membre.notes" class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h3 class="mb-2 text-sm font-semibold uppercase tracking-wider text-slate-500">Notes</h3>
                <p class="text-sm text-slate-600 whitespace-pre-line">{{ membre.notes }}</p>
            </div>

            <!-- Zone danger : suppression réservée au superviseur / admin -->
            <div v-if="canDeleteMembre" class="rounded-xl border border-red-200 bg-red-50 p-5">
                <h3 class="text-sm font-semibold text-red-800">Zone de danger</h3>
                <div v-if="!confirmDelete" class="mt-3">
                    <button @click="confirmDelete = true" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700">
                        Supprimer ce membre
                    </button>
                </div>
                <div v-else class="mt-3 flex items-center gap-3">
                    <p class="text-sm text-red-700">Confirmer la suppression définitive ?</p>
                    <button @click="deleteMembre" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white">Oui, supprimer</button>
                    <button @click="confirmDelete = false" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm text-slate-600">Annuler</button>
                </div>
            </div>
            <div v-else class="rounded-xl border border-amber-200 bg-amber-50 p-5">
                <h3 class="text-sm font-semibold text-amber-800">Suppression du membre</h3>
                <p class="mt-2 text-sm text-amber-700">La suppression définitive d’un membre doit être validée par le superviseur. Contactez votre superviseur de FD pour toute demande de suppression.</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
