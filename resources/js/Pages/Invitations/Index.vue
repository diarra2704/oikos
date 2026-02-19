<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps<{
    invitations: { data: any[]; links: any[] };
    canCreate: boolean;
    canEditAll: boolean;
}>();

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });
}

function supprimer(invitation: { id: number; nom_invite: string }) {
    if (confirm(`Supprimer l'invitation pour « ${invitation.nom_invite } » ?`)) {
        router.delete(route('invitations.destroy', invitation.id));
    }
}
</script>

<template>
    <Head title="Invitations au culte" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-lg font-semibold text-slate-800">Invitations au culte</h1>
                <Link
                    v-if="canCreate"
                    :href="route('invitations.create')"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700"
                >
                    + Nouvelle invitation
                </Link>
            </div>
        </template>

        <div class="space-y-4">
            <p class="text-sm text-slate-500">
                Enregistrez les personnes que vous invitez au culte. Marquez « Venu au culte » une fois qu'elles ont assisté — vous recevrez 1 point par invité venu.
            </p>

            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                <ul class="divide-y divide-slate-100">
                    <li
                        v-for="inv in invitations.data"
                        :key="inv.id"
                        class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 sm:flex-nowrap"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-slate-800">{{ inv.nom_invite }}</span>
                                <span
                                    v-if="inv.est_venu"
                                    class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700"
                                >
                                    Venu au culte
                                </span>
                                <span
                                    v-if="inv.devenu_membre"
                                    class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700"
                                >
                                    Devenu membre
                                </span>
                            </div>
                            <p v-if="inv.telephone_invite" class="text-xs text-slate-500">{{ inv.telephone_invite }}</p>
                            <p class="text-xs text-slate-400">
                                Événement : {{ formatDate(inv.date_evenement) }}
                                <span v-if="inv.inviteur"> · Par {{ inv.inviteur.prenom }} {{ inv.inviteur.nom }}</span>
                            </p>
                            <p v-if="inv.nouveau_membre" class="mt-0.5 text-xs text-blue-600">
                                Lié au membre : {{ inv.nouveau_membre.prenom }} {{ inv.nouveau_membre.nom }}
                            </p>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            <Link
                                :href="route('invitations.edit', inv.id)"
                                class="rounded bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-200"
                            >
                                Modifier
                            </Link>
                            <button
                                type="button"
                                @click="supprimer(inv)"
                                class="rounded bg-slate-100 px-2 py-1 text-xs text-slate-600 hover:bg-red-100 hover:text-red-600"
                            >
                                Supprimer
                            </button>
                        </div>
                    </li>
                </ul>
                <div v-if="!invitations.data?.length" class="p-8 text-center text-sm text-slate-400">
                    Aucune invitation enregistrée. Cliquez sur « Nouvelle invitation » pour en ajouter une.
                </div>
                <div v-if="invitations.links?.length > 1" class="flex flex-wrap justify-center gap-2 border-t border-slate-100 px-4 py-3">
                    <Link
                        v-for="link in invitations.links"
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
