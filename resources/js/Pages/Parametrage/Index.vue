<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps<{
    typeCourant: string;
    types: Record<string, string>;
    items: { id: number; type: string; valeur: string; libelle: string; ordre: number; actif: boolean }[];
}>();

const typeLabel = computed(() => props.types[props.typeCourant] || props.typeCourant);

const formAdd = useForm({
    type: props.typeCourant,
    valeur: '',
    libelle: '',
    ordre: '',
});

const editingId = ref<number | null>(null);
const formEdit = useForm({
    valeur: '',
    libelle: '',
    ordre: 0,
    actif: true,
});

function startEdit(item: typeof props.items[0]) {
    editingId.value = item.id;
    formEdit.valeur = item.valeur;
    formEdit.libelle = item.libelle;
    formEdit.ordre = item.ordre;
    formEdit.actif = item.actif;
    formEdit.clearErrors();
}

function cancelEdit() {
    editingId.value = null;
}

function submitEdit(itemId: number) {
    formEdit.put(route('parametrage.update', itemId), {
        onSuccess: () => { editingId.value = null; },
        preserveState: true,
    });
}

function submitAdd() {
    formAdd.type = props.typeCourant;
    formAdd.post(route('parametrage.store'), {
        onSuccess: () => formAdd.reset('valeur', 'libelle', 'ordre'),
        preserveState: true,
    });
}

function confirmDelete(item: { id: number; libelle: string }) {
    if (confirm(`Supprimer « ${item.libelle } » ?`)) {
        router.delete(route('parametrage.destroy', item.id), { preserveState: false });
    }
}
</script>

<template>
    <Head title="Paramètrage" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-lg font-semibold text-slate-800">Paramètrage</h1>
        </template>

        <div class="space-y-6">
            <!-- Lien Familles d'impact + Onglets par type -->
            <div class="flex flex-wrap items-center gap-2">
                <Link
                    :href="route('parametrage.familles-impact.index')"
                    class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-2.5 text-sm font-medium text-emerald-800 transition hover:bg-emerald-100"
                >
                    Familles d'impact (églises de maison)
                </Link>
                <Link
                    :href="route('parametrage.departements.index')"
                    class="rounded-lg border border-blue-300 bg-blue-50 px-4 py-2.5 text-sm font-medium text-blue-800 transition hover:bg-blue-100"
                >
                    Départements / services
                </Link>
            </div>
            <div class="rounded-xl bg-white p-2 shadow-sm ring-1 ring-slate-200">
                <nav class="flex flex-wrap gap-1" aria-label="Types de paramètres">
                    <Link
                        v-for="(label, key) in types"
                        :key="key"
                        :href="route('parametrage.index', { type: key })"
                        class="rounded-lg px-4 py-2.5 text-sm font-medium transition"
                        :class="typeCourant === key
                            ? 'bg-blue-600 text-white'
                            : 'text-slate-600 hover:bg-slate-100'"
                    >
                        {{ label }}
                    </Link>
                </nav>
            </div>

            <!-- Bloc courant : liste + ajout -->
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="mb-4 text-base font-semibold text-slate-800">{{ typeLabel }}</h2>

                <!-- Formulaire d'ajout -->
                <form @submit.prevent="submitAdd" class="mb-6 flex flex-wrap items-end gap-4 rounded-lg border border-slate-200 bg-slate-50/50 p-4">
                    <div class="min-w-[120px]">
                        <label class="mb-1 block text-xs font-medium text-slate-600">Valeur (code)</label>
                        <input
                            v-model="formAdd.valeur"
                            type="text"
                            required
                            maxlength="100"
                            placeholder="Ex: NA, enseignant"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                        <p v-if="formAdd.errors.valeur" class="mt-1 text-xs text-red-500">{{ formAdd.errors.valeur }}</p>
                    </div>
                    <div class="min-w-[180px]">
                        <label class="mb-1 block text-xs font-medium text-slate-600">Libellé</label>
                        <input
                            v-model="formAdd.libelle"
                            type="text"
                            required
                            maxlength="191"
                            placeholder="Ex: Nouveau Arrivant"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                        <p v-if="formAdd.errors.libelle" class="mt-1 text-xs text-red-500">{{ formAdd.errors.libelle }}</p>
                    </div>
                    <div class="w-20">
                        <label class="mb-1 block text-xs font-medium text-slate-600">Ordre</label>
                        <input
                            v-model.number="formAdd.ordre"
                            type="number"
                            min="0"
                            placeholder="0"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>
                    <button
                        type="submit"
                        :disabled="formAdd.processing"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700 disabled:opacity-50"
                    >
                        {{ formAdd.processing ? 'Ajout...' : 'Ajouter' }}
                    </button>
                </form>

                <!-- Liste des éléments -->
                <div class="overflow-hidden rounded-lg border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Valeur</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Libellé</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Ordre</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Actif</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            <tr v-for="item in items" :key="item.id" class="hover:bg-slate-50/50">
                                <template v-if="editingId === item.id">
                                    <td class="px-4 py-2">
                                        <input v-model="formEdit.valeur" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm" maxlength="100" />
                                        <p v-if="formEdit.errors.valeur" class="text-xs text-red-500">{{ formEdit.errors.valeur }}</p>
                                    </td>
                                    <td class="px-4 py-2">
                                        <input v-model="formEdit.libelle" class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm" maxlength="191" />
                                        <p v-if="formEdit.errors.libelle" class="text-xs text-red-500">{{ formEdit.errors.libelle }}</p>
                                    </td>
                                    <td class="px-4 py-2">
                                        <input v-model.number="formEdit.ordre" type="number" min="0" class="w-20 rounded border border-slate-300 px-2 py-1.5 text-sm" />
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
                                    <td class="px-4 py-3 text-sm font-mono text-slate-700">{{ item.valeur }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-800">{{ item.libelle }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-600">{{ item.ordre }}</td>
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
                            <tr v-if="!items.length">
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">
                                    Aucun élément. Ajoutez-en un ci-dessus.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
