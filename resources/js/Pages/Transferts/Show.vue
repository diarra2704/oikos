<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    transfert: any;
    familles: any[];
    userRole: string;
}>();

const form = useForm({
    decision: '' as 'valide' | 'rejete' | '',
    fd_destination_id: props.transfert.fd_destination_id,
    commentaire_admin: '',
});

function traiter(decision: 'valide' | 'rejete') {
    form.decision = decision;
    form.put(route('transferts.traiter', props.transfert.id));
}

const statutColors: Record<string, string> = {
    en_attente: 'bg-amber-100 text-amber-700 ring-amber-200',
    valide: 'bg-emerald-100 text-emerald-700 ring-emerald-200',
    rejete: 'bg-red-100 text-red-700 ring-red-200',
};

const statutLabels: Record<string, string> = {
    en_attente: 'En attente de validation',
    valide: 'Validé',
    rejete: 'Rejeté',
};
</script>

<template>
    <Head title="Détail du transfert" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('transferts.index')" class="text-slate-400 hover:text-slate-600">&larr;</Link>
                <h1 class="text-lg font-semibold text-slate-800">Demande de transfert</h1>
            </div>
        </template>

        <div class="mx-auto max-w-lg space-y-5">
            <!-- Statut -->
            <div class="rounded-xl p-4 ring-1" :class="statutColors[transfert.statut]">
                <p class="text-sm font-semibold">{{ statutLabels[transfert.statut] }}</p>
                <p v-if="transfert.demandeur" class="mt-1 text-xs">Demandé par {{ transfert.demandeur.prenom }} {{ transfert.demandeur.nom }}</p>
                <p v-if="transfert.traite_par" class="mt-1 text-xs">
                    Traité par {{ transfert.traite_par?.prenom }} {{ transfert.traite_par?.nom }}
                    le {{ new Date(transfert.traite_le).toLocaleDateString('fr-FR') }}
                </p>
                <p v-if="transfert.updated_by && !transfert.traite_par" class="mt-1 text-xs">Modifié par {{ transfert.updated_by.prenom }} {{ transfert.updated_by.nom }}</p>
            </div>

            <!-- Membre -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wider text-slate-500">Membre</h3>
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-700">
                        {{ transfert.membre?.prenom?.[0] }}{{ transfert.membre?.nom?.[0] }}
                    </div>
                    <div>
                        <p class="text-base font-semibold text-slate-800">{{ transfert.membre?.prenom }} {{ transfert.membre?.nom }}</p>
                        <p class="text-xs text-slate-500">{{ transfert.membre?.statut_spirituel }}</p>
                    </div>
                </div>
            </div>

            <!-- Transfert -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wider text-slate-500">Transfert</h3>
                <div class="flex items-center gap-3">
                    <div class="flex-1 rounded-lg p-3 text-center ring-1 ring-slate-200">
                        <p class="text-xs text-slate-500">De</p>
                        <div class="mx-auto mt-1 h-3 w-3 rounded-full" :style="{ backgroundColor: transfert.fd_source?.couleur }"></div>
                        <p class="mt-1 text-sm font-bold text-slate-800">{{ transfert.fd_source?.nom }}</p>
                    </div>
                    <span class="text-xl text-slate-300">&rarr;</span>
                    <div class="flex-1 rounded-lg p-3 text-center ring-1 ring-slate-200">
                        <p class="text-xs text-slate-500">Vers</p>
                        <div class="mx-auto mt-1 h-3 w-3 rounded-full" :style="{ backgroundColor: transfert.fd_destination?.couleur }"></div>
                        <p class="mt-1 text-sm font-bold" :style="{ color: transfert.fd_destination?.couleur }">{{ transfert.fd_destination?.nom }}</p>
                    </div>
                </div>
            </div>

            <!-- Motif -->
            <div v-if="transfert.motif" class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h3 class="mb-2 text-sm font-semibold uppercase tracking-wider text-slate-500">Motif</h3>
                <p class="text-sm text-slate-700 whitespace-pre-line">{{ transfert.motif }}</p>
            </div>

            <!-- Demandeur -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h3 class="mb-2 text-sm font-semibold uppercase tracking-wider text-slate-500">Demandé par</h3>
                <p class="text-sm text-slate-700">{{ transfert.demandeur?.prenom }} {{ transfert.demandeur?.nom }}</p>
                <p class="text-xs text-slate-500">Le {{ new Date(transfert.created_at).toLocaleDateString('fr-FR') }}</p>
            </div>

            <!-- Commentaire admin (si déjà traité) -->
            <div v-if="transfert.commentaire_admin" class="rounded-xl bg-slate-50 p-5 ring-1 ring-slate-200">
                <h3 class="mb-2 text-sm font-semibold uppercase tracking-wider text-slate-500">Commentaire de l'admin</h3>
                <p class="text-sm text-slate-700 whitespace-pre-line">{{ transfert.commentaire_admin }}</p>
            </div>

            <!-- ═══ Actions admin (seulement si en_attente) ═══ -->
            <div v-if="userRole === 'admin' && transfert.statut === 'en_attente'" class="space-y-4 rounded-xl border-2 border-blue-200 bg-blue-50 p-5">
                <h3 class="text-base font-semibold text-blue-800">Traiter la demande</h3>

                <!-- Modifier la FD de destination -->
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">FD de destination (modifiable)</label>
                    <select v-model="form.fd_destination_id" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option v-for="fd in familles" :key="fd.id" :value="fd.id">{{ fd.nom }}</option>
                    </select>
                </div>

                <!-- Commentaire -->
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Commentaire (optionnel)</label>
                    <textarea
                        v-model="form.commentaire_admin"
                        rows="2"
                        placeholder="Motif de la décision..."
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                    ></textarea>
                </div>

                <!-- Boutons -->
                <div class="flex gap-3">
                    <button
                        @click="traiter('valide')"
                        :disabled="form.processing"
                        class="flex-1 rounded-xl bg-emerald-600 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-emerald-700 disabled:opacity-50 active:scale-[0.98]"
                    >
                        {{ form.processing ? 'Traitement...' : 'Valider le transfert' }}
                    </button>
                    <button
                        @click="traiter('rejete')"
                        :disabled="form.processing"
                        class="flex-1 rounded-xl border border-red-300 bg-white py-3 text-sm font-semibold text-red-600 transition hover:bg-red-50 disabled:opacity-50"
                    >
                        Rejeter
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
