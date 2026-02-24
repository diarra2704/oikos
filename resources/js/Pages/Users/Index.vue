<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type SortColumn = 'nom' | 'email' | 'role' | 'fd' | 'actif';
type SortDir = 'asc' | 'desc';

const page = usePage();
const flash = computed(() => (page.props as any).flash || {});
const generatedPassword = computed(() => (page.props as any).flash?.generated_password as string | undefined);
const generatedUserEmail = computed(() => (page.props as any).flash?.generated_user_email as string | undefined);

const props = defineProps<{
    users: {
        id: number;
        nom: string;
        prenom: string;
        email: string;
        telephone: string | null;
        role: string;
        fd_id: number | null;
        cellule_id: number | null;
        actif: boolean;
        created_at: string;
        famille_disciples?: { id: number; nom: string; couleur: string } | null;
        cellule?: { id: number; nom: string; fd_id: number } | null;
    }[];
}>();

const roleLabels: Record<string, string> = {
    admin: 'Administrateur',
    superviseur: 'Superviseur FD',
    leader_cellule: 'Leader cellule',
    faiseur: 'Faiseur',
};

const sortColumn = ref<SortColumn>('nom');
const sortDir = ref<SortDir>('asc');

function setSort(col: SortColumn) {
    if (sortColumn.value === col) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn.value = col;
        sortDir.value = 'asc';
    }
}

function isSortedBy(col: SortColumn) {
    return sortColumn.value === col;
}

const sortedUsers = computed(() => {
    const list = [...props.users];
    const dir = sortDir.value === 'asc' ? 1 : -1;
    list.sort((a, b) => {
        let cmp = 0;
        switch (sortColumn.value) {
            case 'nom':
                cmp = (a.prenom + ' ' + a.nom).localeCompare(b.prenom + ' ' + b.nom);
                break;
            case 'email':
                cmp = (a.email || '').localeCompare(b.email || '');
                break;
            case 'role':
                cmp = (roleLabels[a.role] || a.role).localeCompare(roleLabels[b.role] || b.role);
                break;
            case 'fd': {
                const fdA = a.famille_disciples?.nom ?? '';
                const fdB = b.famille_disciples?.nom ?? '';
                cmp = fdA.localeCompare(fdB) || (a.cellule?.nom ?? '').localeCompare(b.cellule?.nom ?? '');
                break;
            }
            case 'actif':
                cmp = (a.actif ? 1 : 0) - (b.actif ? 1 : 0);
                break;
        }
        return cmp * dir;
    });
    return list;
});
</script>

<template>
    <Head title="Utilisateurs" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-slate-800">Utilisateurs</h1>
                <Link
                    :href="route('users.create')"
                    class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-blue-700"
                >
                    + Créer un utilisateur
                </Link>
            </div>
        </template>

        <div v-if="flash.success" class="mb-4 rounded-lg bg-emerald-50 p-4 text-sm text-emerald-800 ring-1 ring-emerald-200">
            {{ flash.success }}
        </div>
        <div v-if="generatedPassword" class="mb-4 rounded-lg border-2 border-amber-300 bg-amber-50 p-4 text-sm">
            <p class="font-semibold text-amber-900">Mot de passe temporaire (à communiquer à l'utilisateur)</p>
            <p class="mt-1 font-mono text-amber-900">{{ generatedPassword }}</p>
            <p v-if="generatedUserEmail" class="mt-1 text-amber-800">Email : {{ generatedUserEmail }}</p>
        </div>

        <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                <button type="button" class="flex items-center gap-1 hover:text-slate-700" @click="setSort('nom')">
                                    Nom
                                    <span v-if="isSortedBy('nom')" class="inline-flex text-blue-600">
                                        <svg v-if="sortDir === 'asc'" class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" /></svg>
                                        <svg v-else class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                    </span>
                                </button>
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                <button type="button" class="flex items-center gap-1 hover:text-slate-700" @click="setSort('email')">
                                    Email
                                    <span v-if="isSortedBy('email')" class="inline-flex text-blue-600">
                                        <svg v-if="sortDir === 'asc'" class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" /></svg>
                                        <svg v-else class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                    </span>
                                </button>
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                <button type="button" class="flex items-center gap-1 hover:text-slate-700" @click="setSort('role')">
                                    Rôle
                                    <span v-if="isSortedBy('role')" class="inline-flex text-blue-600">
                                        <svg v-if="sortDir === 'asc'" class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" /></svg>
                                        <svg v-else class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                    </span>
                                </button>
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                <button type="button" class="flex items-center gap-1 hover:text-slate-700" @click="setSort('fd')">
                                    FD / Cellule
                                    <span v-if="isSortedBy('fd')" class="inline-flex text-blue-600">
                                        <svg v-if="sortDir === 'asc'" class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" /></svg>
                                        <svg v-else class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                    </span>
                                </button>
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                <button type="button" class="flex items-center gap-1 hover:text-slate-700" @click="setSort('actif')">
                                    Actif
                                    <span v-if="isSortedBy('actif')" class="inline-flex text-blue-600">
                                        <svg v-if="sortDir === 'asc'" class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" /></svg>
                                        <svg v-else class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                    </span>
                                </button>
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="u in sortedUsers" :key="u.id" class="hover:bg-slate-50/50">
                            <td class="px-4 py-3">
                                <span class="font-medium text-slate-800">{{ u.prenom }} {{ u.nom }}</span>
                                <p v-if="u.telephone" class="text-xs text-slate-500">{{ u.telephone }}</p>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ u.email }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium bg-slate-100 text-slate-700">
                                    {{ roleLabels[u.role] || u.role }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                <span v-if="u.famille_disciples">
                                    <span class="inline-block h-2 w-2 rounded-full mr-1" :style="{ backgroundColor: u.famille_disciples.couleur }"></span>
                                    {{ u.famille_disciples.nom }}
                                    <span v-if="u.cellule" class="text-slate-500"> / {{ u.cellule.nom }}</span>
                                </span>
                                <span v-else class="text-slate-400">—</span>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="u.actif ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'"
                                >
                                    {{ u.actif ? 'Oui' : 'Non' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link
                                    :href="route('users.reset-password', u.id)"
                                    class="inline-flex items-center justify-center rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-blue-600"
                                    title="Réinitialiser le mot de passe"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                    </svg>
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!sortedUsers.length">
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">
                                Aucun utilisateur. Créez-en un pour permettre les connexions.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
