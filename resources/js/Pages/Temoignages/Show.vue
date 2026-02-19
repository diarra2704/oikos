<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';

const props = defineProps<{ temoignage: any }>();
const user = usePage().props.auth.user as any;

const statutColors: Record<string, string> = {
    en_attente: 'bg-amber-100 text-amber-700 ring-amber-200',
    valide: 'bg-emerald-100 text-emerald-700 ring-emerald-200',
    rejete: 'bg-red-100 text-red-700 ring-red-200',
};

const statutLabels: Record<string, string> = {
    en_attente: 'En attente',
    valide: 'Validé',
    rejete: 'Rejeté',
};

function valider(statut: string) {
    router.put(route('temoignages.valider', props.temoignage.id), { statut });
}

function formatDate(dateStr: string): string {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
}
</script>

<template>
    <Head :title="'Témoignage de ' + (temoignage.user?.prenom || '')" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('temoignages.index')" class="text-slate-400 hover:text-slate-600">&larr;</Link>
                <h1 class="text-lg font-semibold text-slate-800">Témoignage</h1>
            </div>
        </template>

        <div class="mx-auto max-w-2xl space-y-6">
            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-100 px-6 py-4">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-purple-100 text-sm font-bold text-purple-700">
                                {{ temoignage.user?.prenom?.[0] }}{{ temoignage.user?.nom?.[0] }}
                            </div>
                            <div>
                                <p class="text-base font-semibold text-slate-800">{{ temoignage.user?.prenom }} {{ temoignage.user?.nom }}</p>
                                <p class="text-sm text-slate-500">{{ formatDate(temoignage.created_at) }}</p>
                            </div>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold ring-1" :class="statutColors[temoignage.statut] || 'bg-slate-100 text-slate-600 ring-slate-200'">
                            {{ statutLabels[temoignage.statut] || temoignage.statut }}
                        </span>
                    </div>
                </div>

                <div class="px-6 py-5">
                    <p class="whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ temoignage.contenu }}</p>
                </div>

                <div v-if="temoignage.valide_par || temoignage.valide_le" class="border-t border-slate-100 px-6 py-3">
                    <p class="text-xs text-slate-500">
                        <span v-if="temoignage.valide_par_user">{{ statutLabels[temoignage.statut] || '' }} par {{ temoignage.valide_par_user?.prenom }} {{ temoignage.valide_par_user?.nom }}</span>
                        <span v-if="temoignage.valide_le"> le {{ formatDate(temoignage.valide_le) }}</span>
                    </p>
                </div>
            </div>

            <div v-if="temoignage.statut === 'en_attente' && ['admin', 'superviseur'].includes(user.role)" class="flex gap-3">
                <button @click="valider('valide')" class="flex-1 rounded-lg bg-emerald-600 py-2.5 text-center text-sm font-medium text-white transition hover:bg-emerald-700">
                    Valider ce témoignage
                </button>
                <button @click="valider('rejete')" class="flex-1 rounded-lg border border-red-300 bg-white py-2.5 text-center text-sm font-medium text-red-600 transition hover:bg-red-50">
                    Rejeter
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
