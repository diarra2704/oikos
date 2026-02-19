<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    cellule: any;
    faiseurs: any[];
    membres: any[];
    leadersPotentiels: any[];
    autresCellules: any[];
    userRole: string;
}>();

const canManage = ['admin', 'superviseur'].includes(props.userRole);

// ── Changer le leader ──
const showLeaderModal = ref(false);
const leaderForm = useForm({ leader_id: props.cellule.leader_id || '' });

function changeLeader() {
    leaderForm.put(route('cellules.update', props.cellule.id), {
        onSuccess: () => { showLeaderModal.value = false; },
    });
}

// ── Réaffecter un faiseur ──
const showReaffectModal = ref(false);
const faiseurAReaffecter = ref<any>(null);
const reaffectForm = useForm({ user_id: 0, cellule_id: '' as any });

function openReaffect(faiseur: any) {
    faiseurAReaffecter.value = faiseur;
    reaffectForm.user_id = faiseur.id;
    reaffectForm.cellule_id = '';
    showReaffectModal.value = true;
}

function submitReaffect() {
    reaffectForm.post(route('cellules.reaffecterFaiseur'), {
        onSuccess: () => { showReaffectModal.value = false; },
    });
}

// ── Supprimer la cellule ──
const showDeleteConfirm = ref(false);

function deleteCellule() {
    router.delete(route('cellules.destroy', props.cellule.id));
}

// ── Renommer la cellule ──
const showRenameModal = ref(false);
const renameForm = useForm({ nom: props.cellule.nom });

function submitRename() {
    renameForm.put(route('cellules.update', props.cellule.id), {
        onSuccess: () => { showRenameModal.value = false; },
    });
}

const statutColors: Record<string, string> = {
    NA: 'bg-amber-100 text-amber-700',
    NC: 'bg-blue-100 text-blue-700',
    fidele: 'bg-emerald-100 text-emerald-700',
    STAR: 'bg-purple-100 text-purple-700',
    faiseur_disciple: 'bg-indigo-100 text-indigo-700',
};
</script>

<template>
    <Head :title="'Cellule ' + cellule.nom" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <button onclick="history.back()" class="text-slate-400 hover:text-slate-600">&larr;</button>
                <h1 class="text-lg font-semibold text-slate-800">{{ cellule.nom }}</h1>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Info cellule + Actions superviseur -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs text-slate-500">FD</p>
                        <p class="text-sm font-semibold text-slate-800">{{ cellule.famille_disciples?.nom }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-slate-500">Leader</p>
                        <p class="text-sm font-semibold text-slate-800">
                            {{ cellule.leader?.prenom || '—' }} {{ cellule.leader?.nom || '' }}
                        </p>
                    </div>
                </div>
                <div v-if="cellule.created_by || cellule.updated_by" class="mt-3 flex flex-wrap gap-4 border-t border-slate-100 pt-3 text-xs text-slate-500">
                    <span v-if="cellule.created_by">Créé par {{ cellule.created_by.prenom }} {{ cellule.created_by.nom }}</span>
                    <span v-if="cellule.updated_by">Modifié par {{ cellule.updated_by.prenom }} {{ cellule.updated_by.nom }}</span>
                </div>

                <!-- Actions superviseur -->
                <div v-if="canManage" class="mt-4 flex flex-wrap gap-2 border-t border-slate-100 pt-4">
                    <button @click="showLeaderModal = true" class="rounded-lg bg-blue-50 px-3 py-2 text-xs font-medium text-blue-700 ring-1 ring-blue-200 transition hover:bg-blue-100">
                        Changer le leader
                    </button>
                    <button @click="showRenameModal = true" class="rounded-lg bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-100">
                        Renommer
                    </button>
                    <button @click="showDeleteConfirm = true" class="rounded-lg bg-red-50 px-3 py-2 text-xs font-medium text-red-700 ring-1 ring-red-200 transition hover:bg-red-100">
                        Supprimer la cellule
                    </button>
                </div>
            </div>

            <!-- Faiseurs -->
            <div>
                <h2 class="mb-3 text-base font-semibold text-slate-700">Faiseurs ({{ faiseurs.length }})</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div v-for="f in faiseurs" :key="f.id" class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">
                                    {{ f.prenom?.[0] }}{{ f.nom?.[0] }}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-slate-800">{{ f.prenom }} {{ f.nom }}</p>
                                    <p class="text-xs text-slate-500 capitalize">{{ f.role?.replace('_', ' ') }} · {{ f.membres_affecter_count }} âmes</p>
                                </div>
                            </div>
                            <button
                                v-if="canManage && autresCellules.length > 0"
                                @click="openReaffect(f)"
                                class="rounded-lg bg-amber-50 px-2.5 py-1.5 text-xs font-medium text-amber-700 ring-1 ring-amber-200 transition hover:bg-amber-100"
                            >
                                Déplacer
                            </button>
                        </div>
                    </div>
                    <div v-if="!faiseurs.length" class="col-span-full rounded-xl bg-slate-50 p-6 text-center text-sm text-slate-400">
                        Aucun faiseur dans cette cellule.
                    </div>
                </div>
            </div>

            <!-- Membres -->
            <div>
                <h2 class="mb-3 text-base font-semibold text-slate-700">Membres ({{ membres.length }})</h2>
                <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="divide-y divide-slate-100">
                        <Link
                            v-for="m in membres" :key="m.id"
                            :href="route('membres.show', m.id)"
                            class="flex items-center gap-3 px-4 py-3 transition hover:bg-slate-50"
                        >
                            <div class="flex h-9 w-9 items-center justify-center rounded-full text-xs font-bold" :class="statutColors[m.statut_spirituel] || 'bg-slate-100 text-slate-600'">
                                {{ m.prenom?.[0] }}{{ m.nom?.[0] }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-800">{{ m.prenom }} {{ m.nom }}</p>
                                <p class="text-xs text-slate-500">{{ m.statut_spirituel }} · {{ m.faiseur?.prenom || 'Non assigné' }}</p>
                            </div>
                        </Link>
                        <div v-if="!membres.length" class="p-6 text-center text-sm text-slate-400">Aucun membre.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ MODAL : Changer le leader ═══ -->
        <div v-if="showLeaderModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" @click.self="showLeaderModal = false">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="text-base font-semibold text-slate-800">Changer le leader de {{ cellule.nom }}</h3>
                <p class="mt-1 text-xs text-slate-500">Le nouveau leader aura le rôle "Leader de cellule".</p>

                <div class="mt-4">
                    <select v-model="leaderForm.leader_id" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">— Aucun leader —</option>
                        <option v-for="u in leadersPotentiels" :key="u.id" :value="u.id">
                            {{ u.prenom }} {{ u.nom }}
                            <span v-if="u.id === cellule.leader_id"> (actuel)</span>
                        </option>
                    </select>
                </div>

                <div class="mt-5 flex gap-3">
                    <button @click="changeLeader" :disabled="leaderForm.processing" class="flex-1 rounded-lg bg-blue-600 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-50">
                        {{ leaderForm.processing ? 'Enregistrement...' : 'Confirmer' }}
                    </button>
                    <button @click="showLeaderModal = false" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-600 transition hover:bg-slate-50">
                        Annuler
                    </button>
                </div>
            </div>
        </div>

        <!-- ═══ MODAL : Réaffecter un faiseur ═══ -->
        <div v-if="showReaffectModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" @click.self="showReaffectModal = false">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="text-base font-semibold text-slate-800">Réaffecter {{ faiseurAReaffecter?.prenom }} {{ faiseurAReaffecter?.nom }}</h3>
                <p class="mt-1 text-xs text-slate-500">Les âmes suivies par ce faiseur seront aussi transférées vers la nouvelle cellule.</p>

                <div class="mt-4">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Nouvelle cellule</label>
                    <select v-model="reaffectForm.cellule_id" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">— Sélectionner —</option>
                        <option v-for="c in autresCellules" :key="c.id" :value="c.id">{{ c.nom }}</option>
                    </select>
                </div>

                <div class="mt-5 flex gap-3">
                    <button @click="submitReaffect" :disabled="reaffectForm.processing || !reaffectForm.cellule_id" class="flex-1 rounded-lg bg-amber-600 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-700 disabled:opacity-50">
                        {{ reaffectForm.processing ? 'Déplacement...' : 'Déplacer' }}
                    </button>
                    <button @click="showReaffectModal = false" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-600 transition hover:bg-slate-50">
                        Annuler
                    </button>
                </div>
            </div>
        </div>

        <!-- ═══ MODAL : Renommer ═══ -->
        <div v-if="showRenameModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" @click.self="showRenameModal = false">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="text-base font-semibold text-slate-800">Renommer la cellule</h3>
                <div class="mt-4">
                    <input v-model="renameForm.nom" type="text" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500" />
                </div>
                <div class="mt-5 flex gap-3">
                    <button @click="submitRename" :disabled="renameForm.processing || !renameForm.nom" class="flex-1 rounded-lg bg-blue-600 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-50">
                        Renommer
                    </button>
                    <button @click="showRenameModal = false" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-600">
                        Annuler
                    </button>
                </div>
            </div>
        </div>

        <!-- ═══ MODAL : Supprimer ═══ -->
        <div v-if="showDeleteConfirm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" @click.self="showDeleteConfirm = false">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="text-base font-semibold text-red-800">Supprimer « {{ cellule.nom }} » ?</h3>
                <p class="mt-2 text-sm text-slate-600">Les faiseurs et membres seront désaffectés de cette cellule (non supprimés).</p>
                <div class="mt-5 flex gap-3">
                    <button @click="deleteCellule" class="flex-1 rounded-lg bg-red-600 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700">
                        Supprimer
                    </button>
                    <button @click="showDeleteConfirm = false" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-600">
                        Annuler
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
