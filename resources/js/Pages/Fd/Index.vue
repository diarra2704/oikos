<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import type { FamilleDisciples } from '@/types';

const page = usePage();
const user = computed(() => (page.props.auth as any)?.user);
const canCreateFd = computed(() => user.value?.role === 'admin');
const canDeleteFd = computed(() => user.value?.role === 'admin');

const props = defineProps<{
    familles: FamilleDisciples[];
    superviseurs: { id: number; nom: string; prenom: string; role: string }[];
}>();

const showCreateFd = ref(false);
const createForm = useForm({
    nom: '',
    description: '',
    couleur: '#3b82f6',
    superviseur_id: '' as number | '',
});

function openCreate() {
    createForm.reset();
    createForm.clearErrors();
    showCreateFd.value = true;
}

function submitCreate() {
    createForm.post(route('fd.store'), {
        onSuccess: () => { showCreateFd.value = false; },
        preserveState: false,
    });
}

const editingFd = ref<FamilleDisciples | null>(null);
const fdForm = useForm({
    nom: '',
    description: '',
    couleur: '#3b82f6',
    superviseur_id: '' as number | '',
});

function openEdit(fd: FamilleDisciples) {
    editingFd.value = fd;
    fdForm.nom = fd.nom;
    fdForm.description = fd.description || '';
    fdForm.couleur = fd.couleur || '#3b82f6';
    fdForm.superviseur_id = fd.superviseur_id ?? '';
    fdForm.clearErrors();
    showCreateFd.value = false;
}

function submitEdit() {
    if (!editingFd.value) return;
    fdForm.put(route('fd.update', editingFd.value.id), {
        onSuccess: () => { editingFd.value = null; },
        preserveState: false,
    });
}

function confirmDelete() {
    if (!editingFd.value) return;
    if (!confirm(`Supprimer la Famille de Disciples « ${editingFd.value.nom } » ? Les cellules seront supprimées, les membres et serviteurs seront désaffectés.`)) return;
    router.delete(route('fd.destroy', editingFd.value.id), {
        preserveState: false,
    });
}
</script>

<template>
    <Head title="Familles de Disciples" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-lg font-semibold text-slate-800">Familles de Disciples</h1>
                <button
                    v-if="canCreateFd"
                    type="button"
                    @click="openCreate"
                    class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-700"
                >
                    + Ajouter une FD
                </button>
            </div>
        </template>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="fd in familles" :key="fd.id"
                class="group relative overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200 transition hover:shadow-md"
            >
                <Link :href="route('fd.show', fd.id)" class="block">
                    <div class="h-2" :style="{ backgroundColor: fd.couleur || '#e2e8f0' }"></div>
                    <div class="p-5">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-bold text-slate-800 group-hover:text-blue-600">{{ fd.nom }}</h3>
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :style="{ backgroundColor: (fd.couleur || '#94a3b8') + '20', color: fd.couleur || '#64748b' }">
                                {{ fd.membres_count }} mbr
                            </span>
                        </div>
                        <p class="mt-2 text-sm text-slate-500">
                            Superviseur : <span class="font-medium text-slate-700">{{ fd.superviseur?.prenom || '—' }} {{ fd.superviseur?.nom || '' }}</span>
                        </p>
                        <div class="mt-4 flex gap-6 border-t border-slate-100 pt-3 text-xs text-slate-500">
                            <div><span class="font-semibold text-slate-700">{{ fd.cellules_count }}</span> cellules</div>
                            <div><span class="font-semibold text-slate-700">{{ fd.users_count }}</span> serviteurs</div>
                        </div>
                    </div>
                </Link>
                <div class="absolute right-3 top-8">
                    <button
                        type="button"
                        @click.prevent="openEdit(fd)"
                        class="rounded-lg border border-slate-200 bg-white/90 px-2.5 py-1.5 text-xs font-medium text-slate-600 shadow-sm transition hover:bg-slate-50 hover:text-slate-800"
                    >
                        Modifier
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Nouvelle FD -->
        <div v-if="showCreateFd" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" @click.self="showCreateFd = false">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="text-base font-semibold text-slate-800">Nouvelle Famille de Disciples</h3>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Nom *</label>
                        <input v-model="createForm.nom" type="text" required placeholder="Ex: EXCELLENCE" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500" />
                        <p v-if="createForm.errors.nom" class="mt-1 text-xs text-red-600">{{ createForm.errors.nom }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Couleur</label>
                        <div class="flex items-center gap-3">
                            <input v-model="createForm.couleur" type="color" class="h-10 w-14 cursor-pointer rounded border border-slate-300" />
                            <input v-model="createForm.couleur" type="text" maxlength="7" placeholder="#3b82f6" class="flex-1 rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-mono focus:border-blue-500 focus:ring-blue-500" />
                        </div>
                        <p v-if="createForm.errors.couleur" class="mt-1 text-xs text-red-600">{{ createForm.errors.couleur }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Superviseur</label>
                        <select v-model="createForm.superviseur_id" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">— Aucun —</option>
                            <option v-for="s in superviseurs" :key="s.id" :value="s.id">{{ s.prenom }} {{ s.nom }} ({{ s.role }})</option>
                        </select>
                        <p v-if="createForm.errors.superviseur_id" class="mt-1 text-xs text-red-600">{{ createForm.errors.superviseur_id }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Description (optionnel)</label>
                        <textarea v-model="createForm.description" rows="3" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        <p v-if="createForm.errors.description" class="mt-1 text-xs text-red-600">{{ createForm.errors.description }}</p>
                    </div>
                </div>

                <div class="mt-5 flex gap-3">
                    <button type="button" @click="submitCreate" :disabled="createForm.processing || !createForm.nom" class="flex-1 rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-50">
                        {{ createForm.processing ? 'Création...' : 'Créer' }}
                    </button>
                    <button type="button" @click="showCreateFd = false" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-600 transition hover:bg-slate-50">
                        Annuler
                    </button>
                </div>
            </div>
        </div>
        <!-- Modal Modifier FD -->
        <div v-if="editingFd" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" @click.self="editingFd = null">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="text-base font-semibold text-slate-800">Modifier {{ editingFd.nom }}</h3>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Nom</label>
                        <input v-model="fdForm.nom" type="text" required class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500" />
                        <p v-if="fdForm.errors.nom" class="mt-1 text-xs text-red-600">{{ fdForm.errors.nom }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Couleur</label>
                        <div class="flex items-center gap-3">
                            <input v-model="fdForm.couleur" type="color" class="h-10 w-14 cursor-pointer rounded border border-slate-300" />
                            <input v-model="fdForm.couleur" type="text" maxlength="7" placeholder="#3b82f6" class="flex-1 rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-mono focus:border-blue-500 focus:ring-blue-500" />
                        </div>
                        <p v-if="fdForm.errors.couleur" class="mt-1 text-xs text-red-600">{{ fdForm.errors.couleur }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Superviseur</label>
                        <select v-model="fdForm.superviseur_id" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">— Aucun —</option>
                            <option v-for="s in superviseurs" :key="s.id" :value="s.id">{{ s.prenom }} {{ s.nom }} ({{ s.role }})</option>
                        </select>
                        <p v-if="fdForm.errors.superviseur_id" class="mt-1 text-xs text-red-600">{{ fdForm.errors.superviseur_id }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Description (optionnel)</label>
                        <textarea v-model="fdForm.description" rows="3" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        <p v-if="fdForm.errors.description" class="mt-1 text-xs text-red-600">{{ fdForm.errors.description }}</p>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap gap-3">
                    <button type="button" @click="submitEdit" :disabled="fdForm.processing || !fdForm.nom" class="flex-1 rounded-lg bg-blue-600 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-50">
                        {{ fdForm.processing ? 'Enregistrement...' : 'Enregistrer' }}
                    </button>
                    <button type="button" @click="editingFd = null" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-600 transition hover:bg-slate-50">
                        Annuler
                    </button>
                    <button
                        v-if="canDeleteFd"
                        type="button"
                        @click="confirmDelete"
                        class="w-full rounded-lg border border-red-200 bg-red-50 py-2.5 text-sm font-medium text-red-700 transition hover:bg-red-100"
                    >
                        Supprimer cette FD
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
