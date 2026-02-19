<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';

defineProps<{ temoignages: any }>();

const user = usePage().props.auth.user as any;

const statutColors: Record<string, string> = {
    en_attente: 'bg-amber-100 text-amber-700',
    valide: 'bg-emerald-100 text-emerald-700',
    rejete: 'bg-red-100 text-red-700',
};

function valider(id: number, statut: string) {
    router.put(route('temoignages.valider', id), { statut });
}
</script>

<template>
    <Head title="Témoignages" />
    <AuthenticatedLayout>
        <template #header><h1 class="text-lg font-semibold text-slate-800">Témoignages</h1></template>

        <div class="space-y-4">
            <div class="flex justify-end">
                <Link :href="route('temoignages.create')" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-blue-700">
                    + Partager un témoignage
                </Link>
            </div>

            <div class="space-y-4">
                <div v-for="t in temoignages.data" :key="t.id" class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-start justify-between">
                        <Link :href="route('temoignages.show', t.id)" class="flex items-center gap-3 hover:opacity-80">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-purple-100 text-sm font-bold text-purple-700">
                                {{ t.user?.prenom?.[0] }}{{ t.user?.nom?.[0] }}
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-800">{{ t.user?.prenom }} {{ t.user?.nom }}</p>
                                <p class="text-xs text-slate-500">{{ new Date(t.created_at).toLocaleDateString('fr-FR') }}</p>
                            </div>
                        </Link>
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="statutColors[t.statut] || 'bg-slate-100 text-slate-600'">
                            {{ t.statut === 'en_attente' ? 'En attente' : t.statut === 'valide' ? 'Validé' : 'Rejeté' }}
                        </span>
                    </div>

                    <Link :href="route('temoignages.show', t.id)" class="mt-4 block text-sm text-slate-700 whitespace-pre-line leading-relaxed hover:text-slate-900">{{ t.contenu }}</Link>

                    <!-- Actions superviseur -->
                    <div v-if="t.statut === 'en_attente' && ['admin', 'superviseur'].includes(user.role)" class="mt-4 flex gap-2 border-t border-slate-100 pt-4">
                        <button @click="valider(t.id, 'valide')" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-700">
                            Valider
                        </button>
                        <button @click="valider(t.id, 'rejete')" class="rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50">
                            Rejeter
                        </button>
                    </div>
                </div>

                <div v-if="!temoignages.data?.length" class="rounded-xl bg-white p-8 text-center text-sm text-slate-400 shadow-sm ring-1 ring-slate-200">
                    Aucun témoignage pour le moment.
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="temoignages.links?.length > 3" class="flex justify-center gap-1">
                <template v-for="link in temoignages.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" class="rounded-lg px-3 py-2 text-sm" :class="link.active ? 'bg-blue-600 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200'" v-html="link.label" />
                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
