<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps<{
    familles: { id: number; nom: string; couleur: string }[];
    cellules: { id: number; nom: string; fd_id: number }[];
    roles: { value: string; label: string }[];
}>();

const form = useForm({
    nom: '',
    prenom: '',
    email: '',
    telephone: '',
    password: '',
    password_confirmation: '',
    role: 'faiseur',
    fd_id: '' as number | '',
    cellule_id: '' as number | '',
    actif: true,
});

const cellulesForFd = computed(() => {
    if (!form.fd_id) return [];
    return props.cellules.filter(c => c.fd_id === form.fd_id);
});

const needFd = computed(() =>
    ['superviseur', 'leader_cellule', 'faiseur'].includes(form.role)
);
const needCellule = computed(() =>
    ['leader_cellule', 'faiseur'].includes(form.role)
);

watch(() => form.fd_id, () => { form.cellule_id = ''; });
watch(() => form.role, () => {
    if (!needFd.value) form.fd_id = '';
    if (!needCellule.value) form.cellule_id = '';
});

function submit() {
    form.post(route('users.store'));
}
</script>

<template>
    <Head title="Créer un utilisateur" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('users.index')" class="text-slate-400 hover:text-slate-600">&larr;</Link>
                <h1 class="text-lg font-semibold text-slate-800">Créer un utilisateur</h1>
            </div>
        </template>

        <form @submit.prevent="submit" class="mx-auto max-w-xl space-y-6">
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-slate-500">Identité</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Prénom</label>
                        <input v-model="form.prenom" type="text" required class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500" />
                        <p v-if="form.errors.prenom" class="mt-1 text-xs text-red-500">{{ form.errors.prenom }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Nom</label>
                        <input v-model="form.nom" type="text" required class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500" />
                        <p v-if="form.errors.nom" class="mt-1 text-xs text-red-500">{{ form.errors.nom }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Email (identifiant de connexion)</label>
                        <input v-model="form.email" type="email" required class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500" />
                        <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Téléphone</label>
                        <input v-model="form.telephone" type="tel" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500" />
                    </div>
                </div>
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-slate-500">Connexion (compte créé directement)</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Mot de passe</label>
                        <input v-model="form.password" type="password" minlength="8" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500" />
                        <p class="mt-1 text-xs text-slate-500">Minimum 8 caractères. Laisser vide pour générer un mot de passe temporaire (affiché après création).</p>
                        <p v-if="form.errors.password" class="mt-1 text-xs text-red-500">{{ form.errors.password }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Confirmer le mot de passe</label>
                        <input v-model="form.password_confirmation" type="password" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500" />
                    </div>
                </div>
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-slate-500">Rôle et affectation</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Rôle</label>
                        <select v-model="form.role" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option v-for="r in roles" :key="r.value" :value="r.value">{{ r.label }}</option>
                        </select>
                    </div>
                    <div v-if="needFd">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Famille de Disciples</label>
                        <select v-model="form.fd_id" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500" :required="needFd">
                            <option value="">— Choisir une FD —</option>
                            <option v-for="f in familles" :key="f.id" :value="f.id">{{ f.nom }}</option>
                        </select>
                        <p v-if="form.errors.fd_id" class="mt-1 text-xs text-red-500">{{ form.errors.fd_id }}</p>
                    </div>
                    <div v-if="needCellule && form.fd_id">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Cellule</label>
                        <select v-model="form.cellule_id" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">— Optionnel —</option>
                            <option v-for="c in cellulesForFd" :key="c.id" :value="c.id">{{ c.nom }}</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="flex items-center gap-3">
                            <input v-model="form.actif" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                            <span class="text-sm font-medium text-slate-700">Compte actif (peut se connecter)</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex gap-3">
                <Link :href="route('users.index')" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Annuler</Link>
                <button type="submit" :disabled="form.processing" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">
                    {{ form.processing ? 'Création...' : 'Créer l\'utilisateur' }}
                </button>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
