<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    transferts: any;
    enAttente: number;
    userRole: string;
}>();

const statutColors: Record<string, string> = {
    en_attente: 'bg-amber-100 text-amber-700',
    valide: 'bg-emerald-100 text-emerald-700',
    rejete: 'bg-red-100 text-red-700',
};

const statutLabels: Record<string, string> = {
    en_attente: 'En attente',
    valide: 'Validé',
    rejete: 'Rejeté',
};
</script>

<template>
    <Head title="Transferts" />
    <AuthenticatedLayout>
        <template #header><h1 class="text-lg font-semibold text-slate-800">Transferts de membres</h1></template>

        <div class="space-y-4">
            <!-- Bandeau en attente (admin) -->
            <div v-if="userRole === 'admin' && enAttente > 0" class="flex items-center gap-3 rounded-xl bg-amber-50 p-4 ring-1 ring-amber-200">
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 text-lg font-bold text-amber-700">{{ enAttente }}</span>
                <div>
                    <p class="text-sm font-semibold text-amber-800">{{ enAttente }} demande{{ enAttente > 1 ? 's' : '' }} en attente de validation</p>
                    <p class="text-xs text-amber-600">Cliquez sur une demande pour la traiter.</p>
                </div>
            </div>

            <div class="flex justify-end">
                <Link :href="route('transferts.create')" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-blue-700">
                    {{ userRole === 'admin' ? '+ Transférer un membre' : '+ Demander un transfert' }}
                </Link>
            </div>

            <!-- Liste -->
            <div class="space-y-3">
                <Link
                    v-for="t in transferts.data" :key="t.id"
                    :href="route('transferts.show', t.id)"
                    class="block rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200 transition hover:shadow-md"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-semibold text-slate-800">
                                {{ t.membre?.prenom }} {{ t.membre?.nom }}
                            </p>
                            <div class="mt-1 flex items-center gap-2 text-xs text-slate-500">
                                <span class="font-medium">{{ t.fd_source?.nom }}</span>
                                <span>&rarr;</span>
                                <span class="font-medium">{{ t.fd_destination?.nom }}</span>
                            </div>
                        </div>
                        <span class="flex-shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold" :class="statutColors[t.statut]">
                            {{ statutLabels[t.statut] }}
                        </span>
                    </div>
                    <div class="mt-2 flex items-center justify-between text-xs text-slate-400">
                        <span>Demandé par {{ t.demandeur?.prenom }} {{ t.demandeur?.nom }}</span>
                        <span>{{ new Date(t.created_at).toLocaleDateString('fr-FR') }}</span>
                    </div>
                    <p v-if="t.motif" class="mt-2 text-xs text-slate-500 line-clamp-2">{{ t.motif }}</p>
                </Link>

                <div v-if="!transferts.data?.length" class="rounded-xl bg-white p-8 text-center text-sm text-slate-400 shadow-sm ring-1 ring-slate-200">
                    Aucune demande de transfert.
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="transferts.links?.length > 3" class="flex justify-center gap-1">
                <template v-for="link in transferts.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" class="rounded-lg px-3 py-2 text-sm" :class="link.active ? 'bg-blue-600 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200'" v-html="link.label" />
                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
