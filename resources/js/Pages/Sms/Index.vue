<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const flash = (page.props as any).flash || {};

const props = defineProps<{
    envois: {
        data: {
            id: number;
            nom: string | null;
            message: string;
            fd_id: number | null;
            membre_ids: number[] | null;
            statut: string;
            date_programmee: string | null;
            envoye_at: string | null;
            created_at: string;
            famille_disciples?: { id: number; nom: string; couleur: string } | null;
            created_by?: { id: number; nom: string; prenom: string } | null;
        }[];
        links: any[];
    };
    smsConfigure: boolean;
}>();

const statutLabels: Record<string, string> = {
    programme: 'Programmé',
    en_cours: 'En cours',
    envoye: 'Envoyé',
    annule: 'Annulé',
};

const statutColors: Record<string, string> = {
    programme: 'bg-amber-100 text-amber-800',
    en_cours: 'bg-blue-100 text-blue-800',
    envoye: 'bg-emerald-100 text-emerald-800',
    annule: 'bg-slate-100 text-slate-600',
};
</script>

<template>
    <Head title="Envoi SMS" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-slate-800">Envoi SMS</h1>
                <Link
                    :href="route('sms.create')"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-blue-700"
                >
                    + Nouvel envoi
                </Link>
            </div>
        </template>

        <div v-if="!smsConfigure" class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            SMS non configuré. Ajoutez TWILIO_SID, TWILIO_AUTH_TOKEN, TWILIO_FROM et SMS_ENABLED=true dans .env pour activer l'envoi réel.
        </div>

        <div v-if="flash.success" class="mb-4 rounded-lg bg-emerald-50 p-4 text-sm text-emerald-800 ring-1 ring-emerald-200">
            {{ flash.success }}
        </div>

        <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Message</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">FD</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Statut</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Créé par</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="e in envois.data" :key="e.id" class="hover:bg-slate-50/50">
                            <td class="max-w-xs px-4 py-3">
                                <p class="truncate text-sm text-slate-800">{{ e.message }}</p>
                                <p v-if="e.nom" class="text-xs text-slate-500">{{ e.nom }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span v-if="e.famille_disciples" class="inline-flex items-center gap-1">
                                    <span class="h-2 w-2 rounded-full" :style="{ backgroundColor: e.famille_disciples.couleur }" />
                                    {{ e.famille_disciples.nom }}
                                </span>
                                <span v-else class="text-slate-400">—</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="statutColors[e.statut] || 'bg-slate-100'">
                                    {{ statutLabels[e.statut] || e.statut }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                <span v-if="e.envoye_at">{{ new Date(e.envoye_at).toLocaleString('fr-FR') }}</span>
                                <span v-else-if="e.date_programmee">{{ new Date(e.date_programmee).toLocaleString('fr-FR') }}</span>
                                <span v-else>—</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                {{ e.created_by ? `${e.created_by.prenom} ${e.created_by.nom}` : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link
                                    v-if="e.statut === 'programme'"
                                    :href="route('sms.destroy', e.id)"
                                    method="delete"
                                    as="button"
                                    class="text-sm text-red-600 hover:text-red-700"
                                >
                                    Annuler
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!envois.data.length">
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">
                                Aucun envoi SMS.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="envois.links.length > 3" class="border-t border-slate-200 px-4 py-2">
                <div class="flex justify-center gap-1">
                    <Link
                        v-for="link in envois.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        class="rounded px-3 py-1 text-sm"
                        :class="link.active ? 'bg-blue-600 text-white' : 'text-slate-600 hover:bg-slate-100'"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
