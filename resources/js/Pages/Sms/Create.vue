<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import axios from 'axios';

const props = defineProps<{
    familles: { id: number; nom: string; couleur: string }[];
    userFdId: number | null;
}>();

const programme = ref(false);
const dateProgrammee = ref('');

const form = useForm({
    nom: '',
    message: '',
    fd_id: props.userFdId || props.familles[0]?.id || '',
    membre_ids: [] as number[],
    date_programmee: '' as string | null,
});

const membres = ref<{ id: number; prenom: string; nom: string; telephone: string }[]>([]);
const loadingMembres = ref(false);
const selectAll = ref(true);

watch(() => form.fd_id, async (fdId) => {
    form.membre_ids = [];
    selectAll.value = true;
    if (!fdId) {
        membres.value = [];
        return;
    }
    loadingMembres.value = true;
    try {
        const { data } = await axios.get(route('sms.membres-by-fd'), { params: { fd_id: fdId } });
        membres.value = data.membres || [];
    } catch {
        membres.value = [];
    } finally {
        loadingMembres.value = false;
    }
}, { immediate: true });

const selectedIds = computed({
    get: () => new Set(form.membre_ids),
    set: (v: Set<number>) => { form.membre_ids = [...v]; },
});

function toggleMembre(id: number) {
    const s = new Set(form.membre_ids);
    if (s.has(id)) s.delete(id);
    else s.add(id);
    form.membre_ids = [...s];
}

function toggleSelectAll() {
    selectAll.value = !selectAll.value;
    if (selectAll.value) {
        form.membre_ids = membres.value.map(m => m.id);
    } else {
        form.membre_ids = [];
    }
}

watch(selectAll, (v) => {
    if (v) form.membre_ids = membres.value.map(m => m.id);
    else form.membre_ids = [];
});

const nbDestinataires = computed(() => {
    if (!form.membre_ids.length) return membres.value.length;
    return form.membre_ids.length;
});

function submit() {
    form.date_programmee = programme.value && dateProgrammee.value ? dateProgrammee.value : null;
    form.post(route('sms.store'));
}
</script>

<template>
    <Head title="Nouvel envoi SMS" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('sms.index')" class="text-slate-400 hover:text-slate-600">&larr;</Link>
                <h1 class="text-lg font-semibold text-slate-800">Nouvel envoi SMS</h1>
            </div>
        </template>

        <form @submit.prevent="submit" class="mx-auto max-w-2xl space-y-6">
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-slate-500">Ciblage</h2>
                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Famille de Disciples</label>
                        <select v-model="form.fd_id" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                            <option value="">— Choisir —</option>
                            <option v-for="f in familles" :key="f.id" :value="f.id">{{ f.nom }}</option>
                        </select>
                        <p v-if="form.errors.fd_id" class="mt-1 text-xs text-red-500">{{ form.errors.fd_id }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Destinataires</label>
                        <p class="mb-2 text-xs text-slate-500">
                            Cochez les membres à cibler, ou laissez tout sélectionné pour envoyer à toute la FD.
                        </p>
                        <div v-if="loadingMembres" class="rounded-lg bg-slate-50 py-4 text-center text-sm text-slate-500">
                            Chargement...
                        </div>
                        <div v-else class="max-h-48 overflow-y-auto rounded-lg border border-slate-200 p-2">
                            <label class="mb-2 flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 hover:bg-slate-50">
                                <input v-model="selectAll" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                                <span class="text-sm font-medium text-slate-700">Tous les membres avec téléphone</span>
                            </label>
                            <div class="space-y-1">
                                <label
                                    v-for="m in membres"
                                    :key="m.id"
                                    class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 hover:bg-slate-50"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="form.membre_ids.length === 0 || form.membre_ids.includes(m.id)"
                                        @change="toggleMembre(m.id)"
                                    />
                                    <span class="text-sm text-slate-800">{{ m.prenom }} {{ m.nom }}</span>
                                    <span class="text-xs text-slate-500">{{ m.telephone }}</span>
                                </label>
                            </div>
                            <p v-if="!membres.length && form.fd_id" class="py-2 text-center text-sm text-slate-500">
                                Aucun membre avec numéro de téléphone dans cette FD.
                            </p>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">{{ nbDestinataires }} destinataire(s)</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-slate-500">Message</h2>
                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Nom de la campagne (optionnel)</label>
                        <input v-model="form.nom" type="text" placeholder="Ex: Rappel culte dimanche" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Message *</label>
                        <textarea
                            v-model="form.message"
                            rows="4"
                            required
                            maxlength="1600"
                            placeholder="Votre message SMS..."
                            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                        <p class="mt-1 text-xs text-slate-500">{{ form.message.length }} / 1600 caractères</p>
                        <p v-if="form.errors.message" class="mt-1 text-xs text-red-500">{{ form.errors.message }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-slate-500">Programmation</h2>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Envoyer immédiatement ou programmer ?</label>
                    <label class="mr-4 inline-flex cursor-pointer items-center gap-2">
                        <input v-model="programme" type="radio" :value="false" class="text-blue-600 focus:ring-blue-500" />
                        <span class="text-sm">Immédiat</span>
                    </label>
                    <label class="inline-flex cursor-pointer items-center gap-2">
                        <input v-model="programme" type="radio" :value="true" class="text-blue-600 focus:ring-blue-500" />
                        <span class="text-sm">Programmer</span>
                    </label>
                </div>
                <div v-if="programme" class="mt-4">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Date et heure d'envoi</label>
                    <input
                        v-model="dateProgrammee"
                        type="datetime-local"
                        :min="new Date().toISOString().slice(0, 16)"
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                    <p v-if="form.errors.date_programmee" class="mt-1 text-xs text-red-500">{{ form.errors.date_programmee }}</p>
                    <p class="mt-1 text-xs text-slate-500">Le SMS sera envoyé automatiquement à la date choisie (assurez-vous que la file d'attente est active : <code class="rounded bg-slate-100 px-1">php artisan queue:work</code>).</p>
                </div>
            </div>

            <div class="flex gap-3">
                <Link :href="route('sms.index')" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Annuler
                </Link>
                <button
                    type="submit"
                    :disabled="!!(form.processing || !form.message || (!membres.length && form.fd_id) || (programme && !dateProgrammee))"
                    class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                >
                    {{ form.processing ? 'Envoi...' : (programme ? 'Programmer l\'envoi' : 'Envoyer maintenant') }}
                </button>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
