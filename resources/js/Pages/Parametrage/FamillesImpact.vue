<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    familles: { id: number; nom: string; quartier: string | null; actif: boolean }[];
}>();

const formAdd = useForm({
    nom: '',
    quartier: '',
    actif: true,
});

const editingId = ref<number | null>(null);
const formEdit = useForm({
    nom: '',
    quartier: '',
    actif: true,
});

function startEdit(item: (typeof props.familles)[0]) {
    editingId.value = item.id;
    formEdit.nom = item.nom;
    formEdit.quartier = item.quartier ?? '';
    formEdit.actif = item.actif;
    formEdit.clearErrors();
}

function cancelEdit() {
    editingId.value = null;
}

function submitEdit(itemId: number) {
    formEdit.put(route('parametrage.familles-impact.update', itemId), {
        onSuccess: () => { editingId.value = null; },
        preserveState: true,
    });
}

function submitAdd() {
    formAdd.post(route('parametrage.familles-impact.store'), {
        onSuccess: () => formAdd.reset('nom', 'quartier'),
        preserveState: true,
    });
}

function confirmDelete(item: { id: number; nom: string }) {
    if (confirm(`Supprimer la famille d'impact « ${item.nom } » ?`)) {
        router.delete(route('parametrage.familles-impact.destroy', item.id), { preserveState: false });
    }
}
</script>

<template>
    <Head title="Familles d'impact" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('parametrage.index')" class="text-slate-400 hover:text-slate-600">&larr;</Link>
                <h1 class="text-lg font-semibold text-slate-800">Familles d'impact (églises de maison)</h1>
            </div>
        </template>

        <div class="space-y-6">
            <p class="text-sm text-slate-600">
                Les familles d'impact encouragent la communion fraternelle, le partage et la croissance. Le faiseur peut connecter ses âmes à une famille d'impact proche.
            </p>

            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="mb-4 text-base font-semibold text-slate-800">Liste des familles d'impact</h2>

                <form @submit.prevent="submitAdd" class="mb-6 flex flex-wrap items-end gap-4 rounded-lg border border-slate-200 bg-slate-50/50 p-4">
                    <div class="min-w-[200px]">
                        <label for="add-nom" class="mb-1 block text-xs font-medium text-slate-600">Nom</label>
                        <input
                            id="add-nom"
                            v-model="formAdd.nom"
                            type="text"
                            required
                            maxlength="191"
                            placeholder="Ex: Famille Centre-ville"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                        <p v-if="formAdd.errors.nom" class="mt-1 text-xs text-red-500">{{ formAdd.errors.nom }}</p>
                    </div>
                    <div class="min-w-[160px]">
                        <label for="add-quartier" class="mb-1 block text-xs font-medium text-slate-600">Quartier</label>
                        <input
                            id="add-quartier"
                            v-model="formAdd.quartier"
                            type="text"
                            maxlength="191"
                            placeholder="Optionnel"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="flex items-center gap-2">
                            <input v-model="formAdd.actif" type="checkbox" class="rounded border-slate-300 text-blue-600" />
                            <span class="text-sm text-slate-600">Actif</span>
                        </label>
                    </div>
                    <button
                        type="submit"
                        :disabled="formAdd.processing"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700 disabled:opacity-50"
                    >
                        {{ formAdd.processing ? 'Ajout...' : 'Ajouter' }}
                    </button>
                </form>

                <div class="overflow-hidden rounded-lg border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Nom</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Quartier</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Actif</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            <tr v-for="item in familles" :key="item.id" class="hover:bg-slate-50/50">
                                <template v-if="editingId === item.id">
                                    <td class="px-4 py-2">
                                        <input id="edit-nom" v-model="formEdit.nom" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm" maxlength="191" />
                                        <p v-if="formEdit.errors.nom" class="text-xs text-red-500">{{ formEdit.errors.nom }}</p>
                                    </td>
                                    <td class="px-4 py-2">
                                        <input id="edit-quartier" v-model="formEdit.quartier" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm" maxlength="191" />
                                    </td>
                                    <td class="px-4 py-2">
                                        <label class="flex items-center gap-2">
                                            <input v-model="formEdit.actif" type="checkbox" class="rounded border-slate-300 text-blue-600" />
                                            <span class="text-sm">Actif</span>
                                        </label>
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        <button type="button" @click="submitEdit(item.id)" :disabled="formEdit.processing" class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                            Enregistrer
                                        </button>
                                        <button type="button" @click="cancelEdit" class="ml-2 text-sm text-slate-500 hover:text-slate-700">Annuler</button>
                                    </td>
                                </template>
                                <template v-else>
                                    <td class="px-4 py-3 text-sm font-medium text-slate-800">{{ item.nom }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-600">{{ item.quartier || '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium" :class="item.actif ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'">
                                            {{ item.actif ? 'Oui' : 'Non' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <button type="button" @click="startEdit(item)" class="text-sm font-medium text-blue-600 hover:text-blue-800">Modifier</button>
                                        <button type="button" @click="confirmDelete(item)" class="ml-3 text-sm font-medium text-red-600 hover:text-red-800">Supprimer</button>
                                    </td>
                                </template>
                            </tr>
                            <tr v-if="!familles.length">
                                <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">
                                    Aucune famille d'impact. Ajoutez-en une ci-dessus.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
