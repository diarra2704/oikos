<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{ presences: any }>();
</script>

<template>
    <Head title="Historique des présences" />
    <AuthenticatedLayout>
        <template #header><h1 class="text-lg font-semibold text-slate-800">Historique des présences</h1></template>

        <div class="space-y-4">
            <div class="flex justify-end">
                <Link :href="route('presences.create')" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-blue-700">
                    Pointer les présences
                </Link>
            </div>

            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Membre</th>
                            <th class="hidden px-4 py-3 sm:table-cell">Type</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3 text-center">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="p in presences.data" :key="p.id" class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-800">
                                {{ p.membre?.prenom }} {{ p.membre?.nom }}
                            </td>
                            <td class="hidden px-4 py-3 text-slate-500 sm:table-cell capitalize">{{ p.type_evenement }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ p.date_evenement }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold" :class="p.present ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'">
                                    {{ p.present ? 'Présent' : 'Absent' }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="!presences.data?.length" class="p-8 text-center text-sm text-slate-400">Aucune présence enregistrée.</div>
            </div>

            <!-- Pagination -->
            <div v-if="presences.links?.length > 3" class="flex justify-center gap-1">
                <template v-for="link in presences.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded-lg px-3 py-2 text-sm"
                        :class="link.active ? 'bg-blue-600 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50'"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
