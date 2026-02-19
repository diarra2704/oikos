<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps<{
    membres: any[];
    familles: any[];
    userFdId: number | null;
    userRole: string;
}>();

const search = ref('');
const selectedMembre = ref<any>(null);

const form = useForm({
    membre_id: '' as any,
    fd_destination_id: '' as any,
    motif: '',
});

const filteredMembres = computed(() => {
    if (!search.value) return props.membres;
    const q = search.value.toLowerCase();
    return props.membres.filter(m =>
        m.prenom.toLowerCase().includes(q) || m.nom.toLowerCase().includes(q)
    );
});

// FD disponibles pour la destination (exclure la FD actuelle du membre)
const fdDestinations = computed(() => {
    if (!selectedMembre.value) return props.familles;
    return props.familles.filter(fd => fd.id !== selectedMembre.value.fd_id);
});

function selectMembre(m: any) {
    selectedMembre.value = m;
    form.membre_id = m.id;
    search.value = '';
}

function clearSelection() {
    selectedMembre.value = null;
    form.membre_id = '';
    form.fd_destination_id = '';
}

function submit() {
    form.post(route('transferts.store'));
}

const fdSourceNom = computed(() => {
    if (!selectedMembre.value) return '';
    return props.familles.find(fd => fd.id === selectedMembre.value.fd_id)?.nom || '—';
});

const statutLabels: Record<string, string> = {
    NA: 'Nouveau Arrivant',
    NC: 'Nouveau Converti',
    fidele: 'Fidèle',
    STAR: 'S.T.A.R',
    faiseur_disciple: 'Faiseur',
};
</script>

<template>
    <Head title="Demande de transfert" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('transferts.index')" class="text-slate-400 hover:text-slate-600">&larr;</Link>
                <h1 class="text-lg font-semibold text-slate-800">
                    {{ userRole === 'admin' ? 'Transférer un membre' : 'Demander un transfert' }}
                </h1>
            </div>
        </template>

        <form @submit.prevent="submit" class="mx-auto max-w-lg space-y-5">
            <!-- Info -->
            <div class="rounded-xl p-4 ring-1" :class="userRole === 'admin' ? 'bg-blue-50 ring-blue-200' : 'bg-amber-50 ring-amber-200'">
                <p class="text-sm font-medium" :class="userRole === 'admin' ? 'text-blue-800' : 'text-amber-800'">
                    {{ userRole === 'admin'
                        ? 'En tant qu\'administrateur, le transfert sera effectué immédiatement.'
                        : 'Votre demande sera soumise à l\'administrateur pour validation.' }}
                </p>
            </div>

            <!-- Étape 1 : Sélectionner le membre -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wider text-slate-500">1. Sélectionner le membre</h2>

                <div v-if="!selectedMembre">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Rechercher un membre..."
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                    <div v-if="search" class="mt-2 max-h-48 overflow-y-auto rounded-lg border border-slate-200">
                        <button
                            v-for="m in filteredMembres" :key="m.id"
                            type="button"
                            @click="selectMembre(m)"
                            class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-slate-50"
                        >
                            <span class="font-medium text-slate-800">{{ m.prenom }} {{ m.nom }}</span>
                            <span class="text-xs text-slate-500">{{ statutLabels[m.statut_spirituel] || m.statut_spirituel }}</span>
                        </button>
                        <div v-if="!filteredMembres.length" class="px-4 py-3 text-sm text-slate-400">Aucun résultat.</div>
                    </div>
                </div>

                <div v-else class="flex items-center justify-between rounded-lg bg-blue-50 px-4 py-3 ring-1 ring-blue-200">
                    <div>
                        <p class="text-sm font-semibold text-slate-800">{{ selectedMembre.prenom }} {{ selectedMembre.nom }}</p>
                        <p class="text-xs text-slate-500">
                            {{ statutLabels[selectedMembre.statut_spirituel] || selectedMembre.statut_spirituel }}
                            · FD actuelle : <span class="font-medium">{{ fdSourceNom }}</span>
                        </p>
                    </div>
                    <button type="button" @click="clearSelection" class="rounded-lg bg-white px-3 py-1 text-xs font-medium text-slate-600 ring-1 ring-slate-300 hover:bg-slate-50">
                        Changer
                    </button>
                </div>

                <p v-if="form.errors.membre_id" class="mt-1 text-xs text-red-500">{{ form.errors.membre_id }}</p>
            </div>

            <!-- Étape 2 : FD de destination -->
            <div v-if="selectedMembre" class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wider text-slate-500">2. FD de destination</h2>

                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                    <button
                        v-for="fd in fdDestinations" :key="fd.id"
                        type="button"
                        @click="form.fd_destination_id = fd.id"
                        class="rounded-xl p-3 text-center text-sm font-medium transition ring-1"
                        :class="form.fd_destination_id === fd.id
                            ? 'ring-2 shadow-md text-white'
                            : 'ring-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
                        :style="form.fd_destination_id === fd.id ? { backgroundColor: fd.couleur, borderColor: fd.couleur } : {}"
                    >
                        <div v-if="form.fd_destination_id !== fd.id" class="mx-auto mb-1 h-3 w-3 rounded-full" :style="{ backgroundColor: fd.couleur }"></div>
                        {{ fd.nom }}
                    </button>
                </div>

                <p v-if="form.errors.fd_destination_id" class="mt-2 text-xs text-red-500">{{ form.errors.fd_destination_id }}</p>
            </div>

            <!-- Étape 3 : Motif -->
            <div v-if="selectedMembre && form.fd_destination_id" class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wider text-slate-500">3. Motif du transfert</h2>
                <textarea
                    v-model="form.motif"
                    rows="3"
                    placeholder="Raison du transfert (déménagement, affinité, réorganisation...)"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                ></textarea>
            </div>

            <!-- Récapitulatif + bouton -->
            <div v-if="selectedMembre && form.fd_destination_id" class="rounded-xl bg-slate-50 p-5 ring-1 ring-slate-200">
                <h3 class="mb-3 text-sm font-semibold text-slate-700">Récapitulatif</h3>
                <div class="flex items-center gap-3">
                    <div class="flex-1 rounded-lg bg-white p-3 text-center ring-1 ring-slate-200">
                        <p class="text-xs text-slate-500">De</p>
                        <p class="text-sm font-bold text-slate-800">{{ fdSourceNom }}</p>
                    </div>
                    <span class="text-lg text-slate-400">&rarr;</span>
                    <div class="flex-1 rounded-lg bg-white p-3 text-center ring-1 ring-slate-200">
                        <p class="text-xs text-slate-500">Vers</p>
                        <p class="text-sm font-bold" :style="{ color: familles.find(f => f.id === form.fd_destination_id)?.couleur }">
                            {{ familles.find(f => f.id === form.fd_destination_id)?.nom }}
                        </p>
                    </div>
                </div>
            </div>

            <button
                v-if="selectedMembre && form.fd_destination_id"
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-xl py-3.5 text-sm font-semibold text-white shadow-lg transition disabled:opacity-50 active:scale-[0.98]"
                :class="userRole === 'admin' ? 'bg-blue-600 hover:bg-blue-700' : 'bg-amber-600 hover:bg-amber-700'"
            >
                {{ form.processing
                    ? 'Envoi...'
                    : userRole === 'admin'
                        ? 'Transférer maintenant'
                        : 'Soumettre la demande' }}
            </button>
        </form>
    </AuthenticatedLayout>
</template>
