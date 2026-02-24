<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    stats: any;
    role: 'admin' | 'superviseur' | 'leader_cellule' | 'faiseur';
}>();

const showRappelForm = ref(false);
const rappelTypeLabels: Record<string, string> = { contacter: 'Contacter', relance_interaction: 'Relance interaction' };
const tomorrow = (() => { const d = new Date(); d.setDate(d.getDate() + 1); return d.toISOString().slice(0, 10); })();
const rappelForm = useForm({
    membre_id: null as number | null,
    type: 'contacter',
    date_souhaitee: tomorrow,
    libelle: '',
});

function submitRappel() {
    if (!rappelForm.membre_id) return;
    rappelForm.post(route('rappels.store'), {
        onSuccess: () => {
            showRappelForm.value = false;
            rappelForm.reset();
            rappelForm.membre_id = null;
            rappelForm.type = 'contacter';
            rappelForm.date_souhaitee = (() => { const d = new Date(); d.setDate(d.getDate() + 1); return d.toISOString().slice(0, 10); })();
            rappelForm.libelle = '';
        },
    });
}

function marquerRappelFait(id: number) {
    router.put(route('rappels.fait', id));
}

function formatDate(dateStr: string): string {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });
}

const roleLabels: Record<string, string> = {
    admin: 'Tableau de bord global',
    superviseur: 'Ma Famille de Disciples',
    leader_cellule: 'Ma Cellule',
    faiseur: 'Mes Âmes',
};

const statutColors: Record<string, string> = {
    NA: 'bg-amber-100 text-amber-700',
    NC: 'bg-blue-100 text-blue-700',
    fidele: 'bg-emerald-100 text-emerald-700',
    STAR: 'bg-purple-100 text-purple-700',
    faiseur_disciple: 'bg-indigo-100 text-indigo-700',
};
</script>

<template>
    <Head title="Tableau de bord" />
    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-lg font-semibold text-slate-800">{{ roleLabels[role] }}</h1>
                <p class="mt-0.5 text-sm text-slate-500">Vue d'ensemble et accès rapides</p>
            </div>
        </template>

        <!-- ═══════ ADMIN DASHBOARD ═══════ -->
        <div v-if="role === 'admin'" class="space-y-8">
            <!-- Actions rapides -->
            <div class="flex flex-wrap gap-3">
                <Link :href="route('membres.create')" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-700 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 transition hover:from-blue-700 hover:to-blue-800 hover:shadow-blue-500/30">
                    <span class="text-lg">+</span> Nouveau membre
                </Link>
                <Link :href="route('presences.create')" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-700 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 transition hover:from-emerald-700 hover:to-emerald-800">
                    ✅ Pointer
                </Link>
                <Link :href="route('rapports.create')" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-violet-700 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-violet-500/25 transition hover:from-violet-700 hover:to-violet-800">
                    📋 Rapport
                </Link>
                <Link :href="route('presences.index')" class="inline-flex items-center gap-2 rounded-xl border-2 border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50">
                    📋 Liste des présences
                </Link>
            </div>

            <!-- Stats Cards -->
            <div>
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wider text-slate-500">Indicateurs</h2>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <Link :href="route('membres.index')" class="group rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 transition hover:shadow-md hover:ring-slate-300">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Membres</p>
                        <p class="mt-2 text-3xl font-bold text-slate-800">{{ stats.total_membres }}</p>
                        <p class="mt-1 text-xs text-blue-600 opacity-0 transition group-hover:opacity-100">Voir la liste →</p>
                    </Link>
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Faiseurs</p>
                        <p class="mt-2 text-3xl font-bold text-blue-600">{{ stats.total_faiseurs }}</p>
                    </div>
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">NA + NC</p>
                        <p class="mt-2 text-3xl font-bold text-emerald-600">{{ stats.total_na + stats.total_nc }}</p>
                    </div>
                    <div class="rounded-2xl bg-red-50/80 p-5 shadow-sm ring-1 ring-red-100">
                        <p class="text-xs font-medium uppercase tracking-wide text-red-600/80">Absents 3+ sem.</p>
                        <p class="mt-2 text-3xl font-bold text-red-600">{{ stats.absents_3_semaines }}</p>
                    </div>
                </div>
            </div>

            <!-- FD Cards -->
            <div>
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-slate-800">Familles de Disciples</h2>
                    <Link :href="route('fd.index')" class="text-sm font-medium text-blue-600 hover:text-blue-700">Voir tout →</Link>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="fd in stats.fd_stats" :key="fd.id"
                        :href="route('fd.show', fd.id)"
                        class="group overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80 transition hover:shadow-lg hover:ring-slate-300"
                    >
                        <div class="h-1.5" :style="{ backgroundColor: fd.couleur }" />
                        <div class="p-5">
                            <div class="flex items-center justify-between">
                                <h3 class="text-base font-bold text-slate-800 group-hover:text-blue-600">{{ fd.nom }}</h3>
                                <span class="rounded-full px-3 py-1 text-xs font-semibold" :style="{ backgroundColor: fd.couleur + '25', color: fd.couleur }">
                                    {{ fd.membres_count }} mbr
                                </span>
                            </div>
                            <p class="mt-2 text-sm text-slate-500">
                                {{ fd.superviseur?.prenom || '—' }} {{ fd.superviseur?.nom || '' }}
                            </p>
                            <div class="mt-4 flex gap-4 text-xs text-slate-500">
                                <span>{{ fd.cellules_count }} cellules</span>
                                <span>{{ fd.users_count }} serviteurs</span>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- Anniversaires du mois -->
            <div v-if="stats.anniversaires_du_mois?.length" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-800">Anniversaires du mois</h2>
                    <Link :href="route('anniversaires.index')" class="text-sm font-medium text-blue-600 hover:text-blue-700">Voir tout →</Link>
                </div>
                <ul class="divide-y divide-slate-50 px-5 py-2">
                    <li v-for="(a, i) in stats.anniversaires_du_mois.slice(0, 8)" :key="i" class="flex items-center justify-between rounded-lg py-3 transition hover:bg-slate-50/80">
                        <Link :href="route('membres.show', a.membre_id)" class="text-sm font-medium text-slate-800 hover:text-blue-600">{{ a.label }}</Link>
                        <span class="text-xs text-slate-500">{{ a.type === 'naissance' ? 'Naissance' : 'Conversion' }} · jour {{ a.jour }}</span>
                    </li>
                </ul>
            </div>

            <!-- Dernières modifications -->
            <div v-if="stats.dernieres_modifications?.length" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-800">Dernières modifications</h2>
                    <Link :href="route('historique.index')" class="text-sm font-medium text-blue-600 hover:text-blue-700">Voir tout →</Link>
                </div>
                <ul class="divide-y divide-slate-50 px-5 py-2">
                    <li v-for="(d, i) in stats.dernieres_modifications" :key="i" class="flex items-center justify-between gap-2 rounded-lg py-3 transition hover:bg-slate-50/80">
                        <Link :href="d.url" class="min-w-0 flex-1 truncate text-sm font-medium text-slate-800 hover:text-blue-600">{{ d.label }}</Link>
                        <span class="shrink-0 text-xs text-slate-500">{{ d.updated_by || d.created_by || '—' }} · {{ d.updated_at ? new Date(d.updated_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' }) : (d.created_at ? new Date(d.created_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' }) : '') }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- ═══════ SUPERVISEUR DASHBOARD ═══════ -->
        <div v-else-if="role === 'superviseur'" class="space-y-8">
            <!-- Actions rapides -->
            <div class="flex flex-wrap gap-3">
                <Link :href="route('membres.create')" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-700 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 transition hover:from-blue-700 hover:to-blue-800">
                    <span class="text-lg">+</span> Nouveau membre
                </Link>
                <Link :href="route('presences.create')" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-700 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 transition hover:from-emerald-700 hover:to-emerald-800">
                    ✅ Pointer
                </Link>
                <Link v-if="stats.fd" :href="route('fd.show', stats.fd.id)" class="inline-flex items-center gap-2 rounded-xl border-2 px-5 py-2.5 text-sm font-semibold shadow-sm transition" :style="{ borderColor: stats.fd.couleur, color: stats.fd.couleur }">
                    👥 Ma FD
                </Link>
                <Link :href="route('presences.index')" class="inline-flex items-center gap-2 rounded-xl border-2 border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50">
                    📋 Liste des présences
                </Link>
            </div>

            <div v-if="stats.fd" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80">
                <div class="h-2" :style="{ backgroundColor: stats.fd.couleur }" />
                <div class="p-5">
                    <h2 class="text-lg font-bold text-slate-800">FD {{ stats.fd.nom }}</h2>
                    <p class="text-sm text-slate-500">{{ stats.fd.cellules_count }} cellules · {{ stats.fd.users_count }} serviteurs</p>
                </div>
            </div>
            <div>
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wider text-slate-500">Indicateurs FD</h2>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <Link :href="route('membres.index')" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 transition hover:shadow-md hover:ring-slate-300">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Membres</p>
                        <p class="mt-2 text-3xl font-bold text-slate-800">{{ stats.total_membres }}</p>
                    </Link>
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">NA</p>
                        <p class="mt-2 text-3xl font-bold text-amber-600">{{ stats.total_na }}</p>
                    </div>
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">NC</p>
                        <p class="mt-2 text-3xl font-bold text-blue-600">{{ stats.total_nc }}</p>
                    </div>
                    <div class="rounded-2xl bg-red-50/80 p-5 shadow-sm ring-1 ring-red-100">
                        <p class="text-xs font-medium uppercase tracking-wide text-red-600/80">Absents 3+ sem.</p>
                        <p class="mt-2 text-3xl font-bold text-red-600">{{ stats.absents_3_semaines }}</p>
                    </div>
                </div>
            </div>
            <div v-if="stats.anniversaires_du_mois?.length" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-800">Anniversaires du mois</h2>
                    <Link :href="route('anniversaires.index')" class="text-sm font-medium text-blue-600 hover:text-blue-700">Voir tout →</Link>
                </div>
                <ul class="divide-y divide-slate-50 px-5 py-2">
                    <li v-for="(a, i) in stats.anniversaires_du_mois.slice(0, 8)" :key="i" class="flex items-center justify-between rounded-lg py-3 transition hover:bg-slate-50/80">
                        <Link :href="route('membres.show', a.membre_id)" class="text-sm font-medium text-slate-800 hover:text-blue-600">{{ a.label }}</Link>
                        <span class="text-xs text-slate-500">{{ a.type === 'naissance' ? 'Naissance' : 'Conversion' }} · jour {{ a.jour }}</span>
                    </li>
                </ul>
            </div>
            <div v-if="stats.dernieres_modifications?.length" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-800">Dernières modifications</h2>
                    <Link :href="route('historique.index')" class="text-sm font-medium text-blue-600 hover:text-blue-700">Voir tout →</Link>
                </div>
                <ul class="divide-y divide-slate-50 px-5 py-2">
                    <li v-for="(d, i) in stats.dernieres_modifications" :key="i" class="flex items-center justify-between gap-2 rounded-lg py-3 transition hover:bg-slate-50/80">
                        <Link :href="d.url" class="min-w-0 flex-1 truncate text-sm font-medium text-slate-800 hover:text-blue-600">{{ d.label }}</Link>
                        <span class="shrink-0 text-xs text-slate-500">{{ d.updated_by || d.created_by || '—' }} · {{ d.updated_at ? new Date(d.updated_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' }) : (d.created_at ? new Date(d.created_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' }) : '') }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- ═══════ LEADER CELLULE DASHBOARD ═══════ -->
        <div v-else-if="role === 'leader_cellule'" class="space-y-8">
            <!-- Actions rapides -->
            <div class="flex flex-wrap gap-3">
                <Link :href="route('presences.create')" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-700 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 transition hover:from-emerald-700 hover:to-emerald-800">
                    ✅ Pointer
                </Link>
                <Link :href="route('membres.create')" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-700 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 transition hover:from-blue-700 hover:to-blue-800">
                    <span class="text-lg">+</span> Nouveau membre
                </Link>
                <Link :href="route('rapports.create')" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-violet-700 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-violet-500/25 transition hover:from-violet-700 hover:to-violet-800">
                    📋 Rapport
                </Link>
                <Link :href="route('presences.index')" class="inline-flex items-center gap-2 rounded-xl border-2 border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50">
                    📋 Présences
                </Link>
            </div>

            <div>
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wider text-slate-500">Indicateurs cellule</h2>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    <Link :href="route('membres.index')" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80 transition hover:shadow-md hover:ring-slate-300">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Membres</p>
                        <p class="mt-2 text-3xl font-bold text-slate-800">{{ stats.total_membres }}</p>
                    </Link>
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Faiseurs</p>
                        <p class="mt-2 text-3xl font-bold text-blue-600">{{ stats.total_faiseurs }}</p>
                    </div>
                    <div class="rounded-2xl bg-red-50/80 p-5 shadow-sm ring-1 ring-red-100">
                        <p class="text-xs font-medium uppercase tracking-wide text-red-600/80">Absents</p>
                        <p class="mt-2 text-3xl font-bold text-red-600">{{ stats.absents_3_semaines }}</p>
                    </div>
                </div>
            </div>

            <div>
                <h2 class="mb-3 text-base font-semibold text-slate-800">Mes Faiseurs</h2>
                <div class="space-y-3">
                <div v-for="f in stats.faiseurs" :key="f.id" class="flex items-center justify-between rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/80 transition hover:shadow-md hover:ring-slate-300">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-700">
                            {{ f.prenom?.[0] }}{{ f.nom?.[0] }}
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-800">{{ f.prenom }} {{ f.nom }}</p>
                            <p class="text-xs text-slate-500">{{ f.membres_affecter_count || 0 }} âmes suivies</p>
                        </div>
                    </div>
                </div>
                </div>
            </div>
            <div v-if="stats.anniversaires_du_mois?.length" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-800">Anniversaires du mois</h2>
                    <Link :href="route('anniversaires.index')" class="text-sm font-medium text-blue-600 hover:text-blue-700">Voir tout →</Link>
                </div>
                <ul class="divide-y divide-slate-50 px-5 py-2">
                    <li v-for="(a, i) in stats.anniversaires_du_mois.slice(0, 8)" :key="i" class="flex items-center justify-between rounded-lg py-3 transition hover:bg-slate-50/80">
                        <Link :href="route('membres.show', a.membre_id)" class="text-sm font-medium text-slate-800 hover:text-blue-600">{{ a.label }}</Link>
                        <span class="text-xs text-slate-500">{{ a.type === 'naissance' ? 'Naissance' : 'Conversion' }} · jour {{ a.jour }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- ═══════ FAISEUR DASHBOARD ═══════ -->
        <div v-else class="space-y-8">
            <!-- Actions rapides -->
            <div class="grid grid-cols-2 gap-4 sm:gap-5">
                <Link :href="route('presences.create')" class="flex flex-col items-center gap-3 rounded-2xl bg-gradient-to-br from-emerald-50 to-emerald-100/80 p-5 ring-1 ring-emerald-200/80 transition hover:shadow-lg hover:ring-emerald-300">
                    <span class="text-3xl">✅</span>
                    <span class="text-sm font-semibold text-emerald-800">Pointer</span>
                </Link>
                <Link :href="route('rapports.create')" class="flex flex-col items-center gap-3 rounded-2xl bg-gradient-to-br from-violet-50 to-violet-100/80 p-5 ring-1 ring-violet-200/80 transition hover:shadow-lg hover:ring-violet-300">
                    <span class="text-3xl">📋</span>
                    <span class="text-sm font-semibold text-violet-800">Rapport hebdo</span>
                </Link>
                <Link :href="route('presences.index')" class="flex flex-col items-center gap-3 rounded-2xl bg-slate-50 p-5 ring-1 ring-slate-200/80 transition hover:shadow-lg hover:bg-slate-100/80 hover:ring-slate-300">
                    <span class="text-3xl">📋</span>
                    <span class="text-sm font-semibold text-slate-800">Présences</span>
                </Link>
                <Link :href="route('rappels.index')" class="flex flex-col items-center gap-3 rounded-2xl bg-gradient-to-br from-amber-50 to-amber-100/80 p-5 ring-1 ring-amber-200/80 transition hover:shadow-lg hover:ring-amber-300">
                    <span class="text-3xl">⏰</span>
                    <span class="text-sm font-semibold text-amber-800">Mes rappels</span>
                </Link>
            </div>

            <div>
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wider text-slate-500">Mes indicateurs</h2>
                <div class="grid grid-cols-3 gap-4">
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Mes âmes</p>
                        <p class="mt-2 text-3xl font-bold text-slate-800">{{ stats.total_ames }}</p>
                    </div>
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/80">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Actives</p>
                        <p class="mt-2 text-3xl font-bold text-emerald-600">{{ stats.ames_actives }}</p>
                    </div>
                    <div class="rounded-2xl bg-red-50/80 p-5 shadow-sm ring-1 ring-red-100">
                        <p class="text-xs font-medium uppercase tracking-wide text-red-600/80">Absentes</p>
                        <p class="mt-2 text-3xl font-bold text-red-600">{{ stats.absents }}</p>
                    </div>
                </div>
            </div>

            <!-- Prochains rappels + création -->
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-800">Prochains rappels</h2>
                    <div class="flex items-center gap-2">
                        <Link v-if="stats.mes_ames?.length" :href="route('rappels.index')" class="text-sm font-medium text-blue-600 hover:text-blue-700">Voir tout</Link>
                        <button
                            v-if="stats.mes_ames?.length"
                            @click="showRappelForm = !showRappelForm"
                            class="rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-amber-700"
                        >
                            {{ showRappelForm ? 'Annuler' : '+ Créer un rappel' }}
                        </button>
                    </div>
                </div>
                <div v-if="showRappelForm" class="border-b border-slate-100 bg-amber-50/50 p-5">
                    <h3 class="mb-3 text-xs font-semibold uppercase text-amber-800">Nouveau rappel</h3>
                    <div class="flex flex-wrap gap-3">
                        <select v-model="rappelForm.membre_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-amber-500" required>
                            <option :value="null">Choisir une âme</option>
                            <option v-for="m in stats.mes_ames" :key="m.id" :value="m.id">{{ m.prenom }} {{ m.nom }}</option>
                        </select>
                        <select v-model="rappelForm.type" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-amber-500">
                            <option value="contacter">Contacter</option>
                            <option value="relance_interaction">Relance interaction</option>
                        </select>
                        <input v-model="rappelForm.date_souhaitee" type="date" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-amber-500" />
                        <input v-model="rappelForm.libelle" type="text" placeholder="Libellé (optionnel)" class="min-w-[140px] rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-amber-500" />
                        <button
                            @click="submitRappel"
                            :disabled="rappelForm.processing || !rappelForm.membre_id"
                            class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-amber-700 disabled:opacity-50"
                        >
                            {{ rappelForm.processing ? 'Enregistrement...' : 'Créer' }}
                        </button>
                    </div>
                    <p v-if="rappelForm.errors.membre_id" class="mt-1 text-xs text-red-500">{{ rappelForm.errors.membre_id }}</p>
                    <p v-if="rappelForm.errors.date_souhaitee" class="mt-1 text-xs text-red-500">{{ rappelForm.errors.date_souhaitee }}</p>
                </div>
                <ul class="divide-y divide-slate-50">
                    <li v-for="r in stats.prochains_rappels" :key="r.id" class="flex items-center justify-between rounded-lg px-5 py-3 transition hover:bg-slate-50/80">
                        <Link :href="route('membres.show', r.membre?.id)" class="flex-1 text-sm font-medium text-slate-800 hover:text-blue-600">
                            {{ rappelTypeLabels[r.type] || r.type }} — {{ r.membre?.prenom }} {{ r.membre?.nom }}
                        </Link>
                        <span class="text-xs text-slate-500">{{ formatDate(r.date_souhaitee) }}</span>
                        <button @click="marquerRappelFait(r.id)" class="ml-2 rounded-lg px-2 py-1 text-xs font-medium text-emerald-600 hover:bg-emerald-50" title="Marquer comme fait">✓</button>
                    </li>
                </ul>
                <p v-if="!stats.prochains_rappels?.length && !showRappelForm" class="px-5 py-8 text-center text-sm text-slate-400">Aucun rappel à venir.</p>
            </div>

            <!-- Liste des âmes -->
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-800">Mes Âmes</h2>
                <Link :href="route('membres.index')" class="text-sm font-medium text-blue-600 hover:text-blue-700">Voir tout →</Link>
            </div>
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80">
                <div class="divide-y divide-slate-100">
                    <Link
                        v-for="ame in stats.mes_ames" :key="ame.id"
                        :href="route('membres.show', ame.id)"
                        class="flex items-center justify-between rounded-lg px-5 py-3 transition hover:bg-slate-50/80"
                    >
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold" :class="statutColors[ame.statut_spirituel] || 'bg-slate-100 text-slate-600'">
                                {{ ame.prenom?.[0] }}{{ ame.nom?.[0] }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-800">{{ ame.prenom }} {{ ame.nom }}</p>
                                <p class="text-xs text-slate-500">{{ ame.statut_spirituel }} · {{ ame.telephone || '' }}</p>
                            </div>
                        </div>
                        <span
                            v-if="!ame.derniere_presence || new Date(ame.derniere_presence) < new Date(Date.now() - 21 * 86400000)"
                            class="flex-shrink-0 rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700"
                        >
                            Absent
                        </span>
                    </Link>
                </div>
                <div v-if="!stats.mes_ames?.length" class="px-5 py-10 text-center text-sm text-slate-400">
                    Aucune âme assignée pour le moment.
                </div>
            </div>
            <div v-if="stats.anniversaires_du_mois?.length" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/80">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-800">Anniversaires du mois</h2>
                    <Link :href="route('anniversaires.index')" class="text-sm font-medium text-blue-600 hover:text-blue-700">Voir tout →</Link>
                </div>
                <ul class="divide-y divide-slate-50 px-5 py-2">
                    <li v-for="(a, i) in stats.anniversaires_du_mois.slice(0, 8)" :key="i" class="flex items-center justify-between rounded-lg py-3 transition hover:bg-slate-50/80">
                        <Link :href="route('membres.show', a.membre_id)" class="text-sm font-medium text-slate-800 hover:text-blue-600">{{ a.label }}</Link>
                        <span class="text-xs text-slate-500">{{ a.type === 'naissance' ? 'Naissance' : 'Conversion' }} · jour {{ a.jour }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
