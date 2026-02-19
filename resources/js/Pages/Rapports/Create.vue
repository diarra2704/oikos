<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps<{
    mesAmes: any[];
    presencesSemaine: any[];
    invitationsSemaine: any[];
    periode: { debut: string; fin: string };
}>();

const step = ref(1);
const totalSteps = 4;

const form = useForm({
    type: 'hebdomadaire',
    periode_debut: props.periode.debut,
    periode_fin: props.periode.fin,
    contenu: {
        ames_presentes: [] as number[],
        total_ames_suivies: props.mesAmes.length,
        invitations_faites: 0,
        invitations_abouties: 0,
        immersions_touchees: 0,
        immersions_disposes: 0,
        difficultes: '',
        actions_semaine: '',
        sujets_priere: '',
    },
});

// Pré-cocher les âmes déjà pointées présentes
const amesPresentes = ref<Record<number, boolean>>({});
props.mesAmes.forEach(m => {
    amesPresentes.value[m.id] = props.presencesSemaine.some(p => p.membre_id === m.id);
});

const totalAmesPresentes = computed(() => Object.values(amesPresentes.value).filter(Boolean).length);

function nextStep() {
    if (step.value < totalSteps) step.value++;
}
function prevStep() {
    if (step.value > 1) step.value--;
}

function submit() {
    form.contenu.ames_presentes = Object.entries(amesPresentes.value)
        .filter(([, v]) => v)
        .map(([k]) => parseInt(k));
    form.contenu.total_ames_suivies = props.mesAmes.length;
    form.post(route('rapports.store'));
}
</script>

<template>
    <Head title="Nouveau rapport" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('rapports.index')" class="text-slate-400 hover:text-slate-600">&larr;</Link>
                <h1 class="text-lg font-semibold text-slate-800">Rapport hebdomadaire</h1>
            </div>
        </template>

        <form @submit.prevent="submit" class="mx-auto max-w-lg">
            <!-- Progress bar -->
            <div class="mb-6">
                <div class="flex items-center justify-between text-xs text-slate-500">
                    <span>Étape {{ step }} / {{ totalSteps }}</span>
                    <span>{{ ['Présences', 'Invitations', 'Activités', 'Récapitulatif'][step - 1] }}</span>
                </div>
                <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-200">
                    <div class="h-full rounded-full bg-blue-600 transition-all" :style="{ width: `${(step / totalSteps) * 100}%` }"></div>
                </div>
            </div>

            <!-- Step 1: Présences des âmes au culte -->
            <div v-show="step === 1" class="space-y-4">
                <div class="rounded-xl bg-blue-50 p-4 ring-1 ring-blue-100">
                    <p class="text-sm font-medium text-blue-800">Âmes présentes au culte</p>
                    <p class="mt-1 text-xs text-blue-600">Nombre d'âmes suivies ayant assisté au culte / nombre total des âmes suivies : {{ totalAmesPresentes }} / {{ mesAmes.length }}</p>
                </div>

                <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                    <button
                        v-for="m in mesAmes" :key="m.id"
                        type="button"
                        @click="amesPresentes[m.id] = !amesPresentes[m.id]"
                        class="flex w-full items-center gap-3 px-4 py-3 text-left transition active:bg-slate-50 border-b border-slate-100 last:border-0"
                    >
                        <div
                            class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full text-sm transition-all"
                            :class="amesPresentes[m.id] ? 'bg-emerald-100 text-emerald-600 ring-2 ring-emerald-400' : 'bg-slate-100 text-slate-400'"
                        >
                            {{ amesPresentes[m.id] ? '✓' : '' }}
                        </div>
                        <span class="text-sm" :class="amesPresentes[m.id] ? 'font-medium text-slate-800' : 'text-slate-500'">
                            {{ m.prenom }} {{ m.nom }}
                        </span>
                    </button>
                    <div v-if="!mesAmes.length" class="p-6 text-center text-sm text-slate-400">
                        Aucune âme assignée.
                    </div>
                </div>
            </div>

            <!-- Step 2: Invitations abouties & Immersions -->
            <div v-show="step === 2" class="space-y-4">
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 space-y-6">
                    <div class="rounded-lg bg-blue-50 p-3 ring-1 ring-blue-100">
                        <p class="text-sm font-medium text-blue-800">Invitations abouties</p>
                        <p class="mt-0.5 text-xs text-blue-600">Nombre d'invités ayant assisté au culte / nombre d'invitations faites</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-slate-600">Invitations faites</label>
                            <div class="flex items-center gap-1">
                                <button type="button" @click="form.contenu.invitations_faites = Math.max(0, form.contenu.invitations_faites - 1)" class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-lg font-bold text-slate-600 hover:bg-slate-200">−</button>
                                <span class="w-12 text-center text-xl font-bold text-slate-800">{{ form.contenu.invitations_faites }}</span>
                                <button type="button" @click="form.contenu.invitations_faites++" class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 text-lg font-bold text-blue-600 hover:bg-blue-200">+</button>
                            </div>
                        </div>
                        <span class="text-slate-400">/</span>
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-slate-600">Invités venus au culte</label>
                            <div class="flex items-center gap-1">
                                <button type="button" @click="form.contenu.invitations_abouties = Math.max(0, form.contenu.invitations_abouties - 1)" class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-lg font-bold text-slate-600 hover:bg-slate-200">−</button>
                                <span class="w-12 text-center text-xl font-bold text-slate-800">{{ form.contenu.invitations_abouties }}</span>
                                <button type="button" @click="form.contenu.invitations_abouties++" class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 text-lg font-bold text-emerald-600 hover:bg-emerald-200">+</button>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-lg bg-purple-50 p-3 ring-1 ring-purple-100">
                        <p class="text-sm font-medium text-purple-800">Immersions</p>
                        <p class="mt-0.5 text-xs text-purple-600">Nombre de personnes disposées à intégrer la FD / nombre de personnes touchées</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-slate-600">Personnes touchées</label>
                            <div class="flex items-center gap-1">
                                <button type="button" @click="form.contenu.immersions_touchees = Math.max(0, form.contenu.immersions_touchees - 1)" class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-lg font-bold text-slate-600 hover:bg-slate-200">−</button>
                                <span class="w-12 text-center text-xl font-bold text-slate-800">{{ form.contenu.immersions_touchees }}</span>
                                <button type="button" @click="form.contenu.immersions_touchees++" class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-lg font-bold text-slate-600 hover:bg-slate-200">+</button>
                            </div>
                        </div>
                        <span class="text-slate-400">/</span>
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-slate-600">Disposées à intégrer la FD</label>
                            <div class="flex items-center gap-1">
                                <button type="button" @click="form.contenu.immersions_disposes = Math.max(0, form.contenu.immersions_disposes - 1)" class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-lg font-bold text-slate-600 hover:bg-slate-200">−</button>
                                <span class="w-12 text-center text-xl font-bold text-slate-800">{{ form.contenu.immersions_disposes }}</span>
                                <button type="button" @click="form.contenu.immersions_disposes++" class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-100 text-lg font-bold text-purple-600 hover:bg-purple-200">+</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 3: Textes libres -->
            <div v-show="step === 3" class="space-y-4">
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 space-y-5">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Actions réalisées cette semaine</label>
                        <textarea v-model="form.contenu.actions_semaine" rows="3" placeholder="Visites, appels, rencontres..." class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Difficultés rencontrées</label>
                        <textarea v-model="form.contenu.difficultes" rows="3" placeholder="Défis, obstacles..." class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Sujets de prière</label>
                        <textarea v-model="form.contenu.sujets_priere" rows="3" placeholder="Points de prière..." class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    </div>
                </div>
            </div>

            <!-- Step 4: Récapitulatif -->
            <div v-show="step === 4" class="space-y-4">
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <h3 class="mb-4 text-base font-semibold text-slate-800">Récapitulatif</h3>
                    <dl class="space-y-3">
                        <div class="flex justify-between">
                            <dt class="text-sm text-slate-500">Période</dt>
                            <dd class="text-sm font-medium text-slate-800">{{ form.periode_debut }} au {{ form.periode_fin }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-slate-500">Âmes présentes au culte</dt>
                            <dd class="text-sm font-bold text-emerald-600">{{ totalAmesPresentes }} / {{ mesAmes.length }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-slate-500">Invitations abouties</dt>
                            <dd class="text-sm font-bold text-blue-600">{{ form.contenu.invitations_abouties }} / {{ form.contenu.invitations_faites }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-slate-500">Immersions</dt>
                            <dd class="text-sm font-bold text-purple-600">{{ form.contenu.immersions_disposes }} / {{ form.contenu.immersions_touchees }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Navigation wizard -->
            <div class="mt-6 flex gap-3">
                <button v-if="step > 1" type="button" @click="prevStep" class="flex-1 rounded-xl border border-slate-300 bg-white py-3.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                    Précédent
                </button>
                <button v-if="step < totalSteps" type="button" @click="nextStep" class="flex-1 rounded-xl bg-blue-600 py-3.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                    Suivant
                </button>
                <button v-if="step === totalSteps" type="submit" :disabled="form.processing" class="flex-1 rounded-xl bg-emerald-600 py-3.5 text-sm font-semibold text-white shadow-lg transition hover:bg-emerald-700 disabled:opacity-50 active:scale-[0.98]">
                    {{ form.processing ? 'Envoi...' : 'Soumettre le rapport' }}
                </button>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
