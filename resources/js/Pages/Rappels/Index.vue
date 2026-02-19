<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps<{
    rappels: any;
}>();

const typeLabels: Record<string, string> = {
    contacter: 'Contacter',
    relance_interaction: 'Relance interaction',
};

function formatDate(dateStr: string): string {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });
}

function marquerFait(id: number) {
    router.put(route('rappels.fait', id));
}

function supprimer(id: number) {
    if (confirm('Supprimer ce rappel ?')) {
        router.delete(route('rappels.destroy', id));
    }
}
</script>

<template>
    <Head title="Mes rappels" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-lg font-semibold text-slate-800">Mes rappels</h1>
        </template>

        <div class="space-y-4">
            <p class="text-sm text-slate-500">Rappels à faire (non marqués comme faits), par date souhaitée.</p>

            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                <ul class="divide-y divide-slate-100">
                    <li
                        v-for="r in rappels.data"
                        :key="r.id"
                        class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 sm:flex-nowrap"
                    >
                        <div class="min-w-0 flex-1">
                            <Link :href="route('membres.show', r.membre?.id)" class="font-medium text-slate-800 hover:text-blue-600">
                                {{ r.membre?.prenom }} {{ r.membre?.nom }}
                            </Link>
                            <span class="ml-2 text-sm text-slate-500">{{ typeLabels[r.type] || r.type }}</span>
                            <p v-if="r.libelle" class="mt-0.5 text-xs text-slate-600">{{ r.libelle }}</p>
                        </div>
                        <span class="text-sm text-slate-500">{{ formatDate(r.date_souhaitee) }}</span>
                        <div class="flex gap-2">
                            <button @click="marquerFait(r.id)" class="rounded bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-200">Marquer fait</button>
                            <button @click="supprimer(r.id)" class="rounded bg-slate-100 px-2 py-1 text-xs text-slate-600 hover:bg-red-100 hover:text-red-600">Supprimer</button>
                        </div>
                    </li>
                </ul>
                <div v-if="!rappels.data?.length" class="p-8 text-center text-sm text-slate-400">
                    Aucun rappel en attente. Créez-en depuis une fiche membre ou le tableau de bord.
                </div>
                <div v-if="rappels.links?.length > 1" class="flex justify-center gap-2 border-t border-slate-100 px-4 py-3">
                    <Link
                        v-for="link in rappels.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        class="rounded px-3 py-1 text-sm"
                        :class="link.active ? 'bg-blue-100 font-medium text-blue-800' : 'text-slate-600 hover:bg-slate-100'"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
