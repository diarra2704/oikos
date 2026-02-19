<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    mesAmes: { id: number; nom: string; prenom: string }[];
}>();

const form = useForm({
    nom_invite: '',
    telephone_invite: '',
    date_evenement: new Date().toISOString().slice(0, 10),
    est_venu: false,
    devenu_membre: false,
    nouveau_membre_id: '' as number | '',
});

function submit() {
    form.post(route('invitations.store'), {
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <Head title="Nouvelle invitation" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('invitations.index')" class="text-slate-400 hover:text-slate-600">&larr;</Link>
                <h1 class="text-lg font-semibold text-slate-800">Nouvelle invitation au culte</h1>
            </div>
        </template>

        <form @submit.prevent="submit" class="mx-auto max-w-lg space-y-6">
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-slate-500">Invité</h2>
                <div class="space-y-4">
                    <div>
                        <label for="nom_invite" class="mb-1 block text-sm font-medium text-slate-700">Nom de l'invité *</label>
                        <input
                            id="nom_invite"
                            v-model="form.nom_invite"
                            type="text"
                            required
                            maxlength="191"
                            placeholder="Ex: Jean Dupont"
                            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                        <p v-if="form.errors.nom_invite" class="mt-1 text-xs text-red-500">{{ form.errors.nom_invite }}</p>
                    </div>
                    <div>
                        <label for="telephone_invite" class="mb-1 block text-sm font-medium text-slate-700">Téléphone (optionnel)</label>
                        <input
                            id="telephone_invite"
                            v-model="form.telephone_invite"
                            type="tel"
                            maxlength="20"
                            placeholder="Ex: 06 12 34 56 78"
                            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>
                    <div>
                        <label for="date_evenement" class="mb-1 block text-sm font-medium text-slate-700">Date de l'événement (culte) *</label>
                        <input
                            id="date_evenement"
                            v-model="form.date_evenement"
                            type="date"
                            required
                            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                        <p v-if="form.errors.date_evenement" class="mt-1 text-xs text-red-500">{{ form.errors.date_evenement }}</p>
                    </div>
                    <div class="flex flex-wrap gap-4">
                        <label class="flex items-center gap-2">
                            <input v-model="form.est_venu" type="checkbox" class="rounded border-slate-300 text-blue-600" />
                            <span class="text-sm text-slate-700">A assisté au culte (1 point)</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input v-model="form.devenu_membre" type="checkbox" class="rounded border-slate-300 text-blue-600" />
                            <span class="text-sm text-slate-700">Devenu membre</span>
                        </label>
                    </div>
                    <div v-if="form.devenu_membre && mesAmes.length">
                        <label for="nouveau_membre_id" class="mb-1 block text-sm font-medium text-slate-700">Lier à un membre (mes âmes)</label>
                        <select
                            id="nouveau_membre_id"
                            v-model="form.nouveau_membre_id"
                            class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">— Choisir —</option>
                            <option v-for="m in mesAmes" :key="m.id" :value="m.id">{{ m.prenom }} {{ m.nom }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex gap-3">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-50"
                >
                    {{ form.processing ? 'Enregistrement...' : 'Enregistrer' }}
                </button>
                <Link
                    :href="route('invitations.index')"
                    class="rounded-lg border border-slate-300 bg-white px-6 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
                >
                    Annuler
                </Link>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
