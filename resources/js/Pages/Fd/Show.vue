<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    fd: any;
    cellules: any[];
    membres: any;
    faiseurs: any[];
    superviseurs?: { id: number; nom: string; prenom: string; role: string }[];
    stats: any;
    userRole?: string;
}>();

const canManage = ['admin', 'superviseur'].includes(props.userRole || '');

const showEditFd = ref(false);
const fdForm = useForm({
    nom: props.fd.nom,
    description: props.fd.description || '',
    couleur: props.fd.couleur || '#3b82f6',
    superviseur_id: props.fd.superviseur_id ?? '',
});

function openEditFd() {
    fdForm.nom = props.fd.nom;
    fdForm.description = props.fd.description || '';
    fdForm.couleur = props.fd.couleur || '#3b82f6';
    fdForm.superviseur_id = props.fd.superviseur_id ?? '';
    fdForm.clearErrors();
    showEditFd.value = true;
}

function submitEditFd() {
    fdForm.put(route('fd.update', props.fd.id), {
        onSuccess: () => showEditFd.value = false,
    });
}

const showCreateCellule = ref(false);
const celluleForm = useForm({
    nom: '',
    fd_id: props.fd.id,
    leader_id: '' as any,
});

function submitCellule() {
    celluleForm.post(route('cellules.store'), {
        onSuccess: () => {
            showCreateCellule.value = false;
            celluleForm.reset('nom', 'leader_id');
        },
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
    <Head :title="'FD ' + fd.nom" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <Link :href="userRole === 'admin' ? route('fd.index') : route('dashboard')" class="text-slate-400 hover:text-slate-600">&larr;</Link>
                    <h1 class="text-lg font-semibold text-slate-800">FD {{ fd.nom }}</h1>
                </div>
                <button
                    v-if="canManage"
                    @click="openEditFd"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    Modifier la FD
                </button>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Stats -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                    <p class="text-xs text-slate-500">Total</p>
                    <p class="mt-1 text-2xl font-bold text-slate-800">{{ stats.total_membres }}</p>
                </div>
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                    <p class="text-xs text-slate-500">NA</p>
                    <p class="mt-1 text-2xl font-bold text-amber-600">{{ stats.na }}</p>
                </div>
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                    <p class="text-xs text-slate-500">NC</p>
                    <p class="mt-1 text-2xl font-bold text-blue-600">{{ stats.nc }}</p>
                </div>
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                    <p class="text-xs text-slate-500">Fidèles</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-600">{{ stats.fideles }}</p>
                </div>
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                    <p class="text-xs text-slate-500">Absents 3+ sem.</p>
                    <p class="mt-1 text-2xl font-bold text-red-600">{{ stats.absents_3sem }}</p>
                </div>
            </div>

            <!-- Cellules -->
            <div>
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-slate-700">Cellules ({{ cellules.length }})</h2>
                    <button
                        v-if="canManage"
                        @click="showCreateCellule = true"
                        class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-700"
                    >
                        + Cellule
                    </button>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <Link
                        v-for="c in cellules" :key="c.id"
                        :href="route('cellules.show', c.id)"
                        class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200 transition hover:shadow-md"
                    >
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-slate-800">{{ c.nom }}</h3>
                            <span class="text-xs text-slate-500">{{ c.membres_count }} mbr</span>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">
                            Leader : {{ c.leader?.prenom || '—' }} {{ c.leader?.nom || '' }}
                            · {{ c.faiseurs_count }} faiseurs
                        </p>
                    </Link>
                    <div v-if="!cellules.length" class="col-span-full rounded-xl bg-slate-50 p-6 text-center text-sm text-slate-400">
                        Aucune cellule. Cliquez sur « + Cellule » pour en créer une.
                    </div>
                </div>
            </div>

            <!-- Membres récents -->
            <div>
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-slate-700">Membres</h2>
                    <Link :href="route('membres.create')" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
                        + Ajouter
                    </Link>
                </div>
                <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="divide-y divide-slate-100">
                        <div v-for="m in membres.data" :key="m.id" class="flex items-center justify-between px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-full text-xs font-bold" :class="statutColors[m.statut_spirituel] || 'bg-slate-100 text-slate-600'">
                                    {{ m.prenom?.[0] }}{{ m.nom?.[0] }}
                                </div>
                                <div>
                                    <Link :href="route('membres.show', m.id)" class="text-sm font-medium text-slate-800 hover:text-blue-600">
                                        {{ m.prenom }} {{ m.nom }}
                                    </Link>
                                    <p class="text-xs text-slate-500">{{ m.statut_spirituel }} · {{ m.faiseur?.prenom || 'Non assigné' }}</p>
                                </div>
                            </div>
                        </div>
                        <div v-if="!membres.data?.length" class="p-6 text-center text-sm text-slate-400">Aucun membre.</div>
                    </div>
                </div>
            </div>
        </div>
        <!-- ═══ MODAL : Modifier la FD ═══ -->
        <div v-if="showEditFd" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" @click.self="showEditFd = false">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="text-base font-semibold text-slate-800">Modifier la Famille de Disciples</h3>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Nom</label>
                        <input v-model="fdForm.nom" type="text" required class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500" />
                        <p v-if="fdForm.errors.nom" class="mt-1 text-xs text-red-600">{{ fdForm.errors.nom }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Couleur</label>
                        <div class="flex items-center gap-3">
                            <input v-model="fdForm.couleur" type="color" class="h-10 w-14 cursor-pointer rounded border border-slate-300" />
                            <input v-model="fdForm.couleur" type="text" maxlength="7" placeholder="#3b82f6" class="flex-1 rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-mono focus:border-blue-500 focus:ring-blue-500" />
                        </div>
                        <p v-if="fdForm.errors.couleur" class="mt-1 text-xs text-red-600">{{ fdForm.errors.couleur }}</p>
                    </div>
                    <div v-if="superviseurs?.length">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Superviseur</label>
                        <select v-model="fdForm.superviseur_id" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">— Aucun —</option>
                            <option v-for="s in superviseurs" :key="s.id" :value="s.id">{{ s.prenom }} {{ s.nom }} ({{ s.role }})</option>
                        </select>
                        <p v-if="fdForm.errors.superviseur_id" class="mt-1 text-xs text-red-600">{{ fdForm.errors.superviseur_id }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Description (optionnel)</label>
                        <textarea v-model="fdForm.description" rows="3" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        <p v-if="fdForm.errors.description" class="mt-1 text-xs text-red-600">{{ fdForm.errors.description }}</p>
                    </div>
                </div>

                <div class="mt-5 flex gap-3">
                    <button @click="submitEditFd" :disabled="fdForm.processing || !fdForm.nom" class="flex-1 rounded-lg bg-blue-600 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-50">
                        {{ fdForm.processing ? 'Enregistrement...' : 'Enregistrer' }}
                    </button>
                    <button type="button" @click="showEditFd = false" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-600 transition hover:bg-slate-50">
                        Annuler
                    </button>
                </div>
            </div>
        </div>
        <!-- ═══ MODAL : Créer une cellule ═══ -->
        <div v-if="showCreateCellule" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" @click.self="showCreateCellule = false">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="text-base font-semibold text-slate-800">Nouvelle cellule</h3>
                <p class="mt-1 text-xs text-slate-500">La cellule sera créée dans la FD {{ fd.nom }}.</p>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Nom de la cellule</label>
                        <input v-model="celluleForm.nom" type="text" placeholder="Ex : Cellule Alpha" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500" />
                        <p v-if="celluleForm.errors.nom" class="mt-1 text-xs text-red-600">{{ celluleForm.errors.nom }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Leader (optionnel)</label>
                        <select v-model="celluleForm.leader_id" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">— Aucun pour l'instant —</option>
                            <option v-for="f in faiseurs" :key="f.id" :value="f.id">{{ f.prenom }} {{ f.nom }}</option>
                        </select>
                    </div>
                </div>

                <div class="mt-5 flex gap-3">
                    <button @click="submitCellule" :disabled="celluleForm.processing || !celluleForm.nom" class="flex-1 rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-50">
                        {{ celluleForm.processing ? 'Création...' : 'Créer' }}
                    </button>
                    <button @click="showCreateCellule = false" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-600 transition hover:bg-slate-50">
                        Annuler
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
