<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps<{
    membres: any[];
    dejaPointees: number[];
    date: string;
    type: string;
}>();

const selectedType = ref(props.type);
const selectedDate = ref(props.date);
const search = ref('');

// Initialiser toutes les présences
const presences = ref<Record<number, boolean>>({});
props.membres.forEach(m => {
    presences.value[m.id] = props.dejaPointees.includes(m.id);
});

const filteredMembres = computed(() => {
    if (!search.value) return props.membres;
    const q = search.value.toLowerCase();
    return props.membres.filter(m =>
        m.prenom.toLowerCase().includes(q) || m.nom.toLowerCase().includes(q)
    );
});

const totalPresents = computed(() => Object.values(presences.value).filter(Boolean).length);
const totalMembres = computed(() => props.membres.length);

function toggleAll(value: boolean) {
    props.membres.forEach(m => { presences.value[m.id] = value; });
}

const form = useForm({
    date_evenement: '',
    type_evenement: '',
    presences: [] as any[],
});

function submit() {
    form.date_evenement = selectedDate.value;
    form.type_evenement = selectedType.value;
    form.presences = props.membres.map(m => ({
        membre_id: m.id,
        present: presences.value[m.id] || false,
    }));
    form.post(route('presences.store'));
}

const types = [
    { value: 'culte', label: 'Culte', icon: '⛪' },
    { value: 'priere', label: 'Prière', icon: '🙏' },
    { value: 'reunion_fd', label: 'Réunion FD', icon: '👥' },
    { value: 'fi', label: 'FI', icon: '🎓' },
    { value: 'formation', label: 'Formation', icon: '📖' },
];
</script>

<template>
    <Head title="Pointer les présences" />
    <AuthenticatedLayout>
        <template #header><h1 class="text-lg font-semibold text-slate-800">Pointer les présences</h1></template>

        <form @submit.prevent="submit" class="mx-auto max-w-lg space-y-4">
            <!-- Type d'événement -->
            <div class="flex gap-2 overflow-x-auto pb-2 -mx-4 px-4 snap-x">
                <button
                    v-for="t in types" :key="t.value"
                    type="button"
                    @click="selectedType = t.value"
                    class="flex snap-start flex-shrink-0 items-center gap-2 rounded-full px-4 py-2.5 text-sm font-medium transition"
                    :class="selectedType === t.value ? 'bg-blue-600 text-white shadow-md' : 'bg-white text-slate-600 ring-1 ring-slate-200'"
                >
                    <span>{{ t.icon }}</span>
                    {{ t.label }}
                </button>
            </div>

            <!-- Date -->
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                <label class="mb-1 block text-xs font-medium text-slate-500">Date de l'événement</label>
                <input v-model="selectedDate" type="date" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500" />
            </div>

            <!-- Compteur + actions rapides -->
            <div class="flex items-center justify-between rounded-xl bg-blue-50 px-4 py-3 ring-1 ring-blue-100">
                <div>
                    <span class="text-2xl font-bold text-blue-700">{{ totalPresents }}</span>
                    <span class="text-sm text-blue-500"> / {{ totalMembres }} présents</span>
                </div>
                <div class="flex gap-2">
                    <button type="button" @click="toggleAll(true)" class="rounded-lg bg-emerald-100 px-3 py-1.5 text-xs font-medium text-emerald-700 transition hover:bg-emerald-200">Tous ✓</button>
                    <button type="button" @click="toggleAll(false)" class="rounded-lg bg-red-100 px-3 py-1.5 text-xs font-medium text-red-700 transition hover:bg-red-200">Aucun ✗</button>
                </div>
            </div>

            <!-- Recherche -->
            <input
                v-model="search"
                type="search"
                placeholder="Rechercher..."
                class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
            />

            <!-- Liste des membres (toggles tactiles) -->
            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="divide-y divide-slate-100">
                    <button
                        v-for="m in filteredMembres" :key="m.id"
                        type="button"
                        @click="presences[m.id] = !presences[m.id]"
                        class="flex w-full items-center gap-3 px-4 py-3 text-left transition active:bg-slate-50"
                    >
                        <div
                            class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full text-lg transition-all"
                            :class="presences[m.id] ? 'bg-emerald-100 text-emerald-600 ring-2 ring-emerald-400' : 'bg-slate-100 text-slate-400'"
                        >
                            {{ presences[m.id] ? '✓' : '·' }}
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium" :class="presences[m.id] ? 'text-slate-800' : 'text-slate-500'">
                                {{ m.prenom }} {{ m.nom }}
                            </p>
                            <p class="text-xs text-slate-400">{{ m.statut_spirituel }}</p>
                        </div>
                    </button>

                    <div v-if="!filteredMembres.length" class="p-6 text-center text-sm text-slate-400">
                        Aucun membre trouvé.
                    </div>
                </div>
            </div>

            <!-- Bouton valider -->
            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-xl bg-blue-600 py-4 text-base font-semibold text-white shadow-lg transition hover:bg-blue-700 disabled:opacity-50 active:scale-[0.98]"
            >
                {{ form.processing ? 'Enregistrement...' : `Valider (${totalPresents} présent${totalPresents > 1 ? 's' : ''})` }}
            </button>
        </form>
    </AuthenticatedLayout>
</template>
