<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps<{ rapports: any }>();

const statutColors: Record<string, string> = {
    soumis: 'bg-amber-100 text-amber-700',
    valide: 'bg-emerald-100 text-emerald-700',
    rejete: 'bg-red-100 text-red-700',
    brouillon: 'bg-slate-100 text-slate-600',
};

function exportTxt() {
    const lines: string[] = [
        'LISTE DES RAPPORTS',
        '='.repeat(50),
        '',
    ];
    (props.rapports.data || []).forEach((r: any) => {
        lines.push(`Rapport ${(r.type || 'hebdo').toUpperCase()}`);
        lines.push(`  Auteur  : ${r.auteur?.prenom ?? ''} ${r.auteur?.nom ?? ''}`);
        lines.push(`  FD      : ${r.famille_disciples?.nom ?? '—'}`);
        lines.push(`  Période : ${r.periode_debut} — ${r.periode_fin}`);
        lines.push(`  Statut  : ${r.statut}`);
        lines.push('');
    });
    if (!(props.rapports.data || []).length) lines.push('Aucun rapport.');
    downloadFile('rapports-liste.txt', lines.join('\n'));
}

function downloadFile(filename: string, content: string) {
    const blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    URL.revokeObjectURL(url);
}
</script>

<template>
    <Head title="Rapports" />
    <AuthenticatedLayout>
        <template #header><h1 class="text-lg font-semibold text-slate-800">Rapports</h1></template>

        <div class="space-y-4">
            <div class="flex flex-wrap justify-end gap-2">
                <button @click="exportTxt" type="button" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    📥 Exporter la liste en TXT
                </button>
                <Link :href="route('rapports.create')" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-blue-700">
                    + Nouveau rapport
                </Link>
            </div>

            <div class="space-y-3">
                <Link
                    v-for="r in rapports.data" :key="r.id"
                    :href="route('rapports.show', r.id)"
                    class="block rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200 transition hover:shadow-md"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-slate-800 capitalize">Rapport {{ r.type }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ r.auteur?.prenom }} {{ r.auteur?.nom }}
                                <span v-if="r.famille_disciples"> · {{ r.famille_disciples.nom }}</span>
                            </p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="statutColors[r.statut] || 'bg-slate-100 text-slate-600'">
                            {{ r.statut }}
                        </span>
                    </div>
                    <div class="mt-2 text-xs text-slate-400">
                        Période : {{ r.periode_debut }} au {{ r.periode_fin }}
                    </div>
                </Link>

                <div v-if="!rapports.data?.length" class="p-8 text-center text-sm text-slate-400">
                    Aucun rapport.
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="rapports.links?.length > 3" class="flex justify-center gap-1">
                <template v-for="link in rapports.links" :key="link.label">
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
