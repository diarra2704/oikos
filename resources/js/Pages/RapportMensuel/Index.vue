<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps<{
    rapports: any;
    userRole: string;
}>();

const moisNom = (mois: string) => {
    const [y, m] = mois.split('-');
    const months = ['Janvier', 'Fevrier', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Aout', 'Septembre', 'Octobre', 'Novembre', 'Decembre'];
    return `${months[parseInt(m) - 1]} ${y}`;
};

function exportTxt() {
    const lines: string[] = [
        'LISTE DES RAPPORTS MENSUELS',
        '='.repeat(50),
        '',
    ];
    (props.rapports.data || []).forEach((r: any) => {
        lines.push(moisNom(r.mois));
        lines.push(`  Faiseur : ${r.faiseur?.prenom ?? ''} ${r.faiseur?.nom ?? ''}`);
        lines.push(`  FD      : ${r.famille_disciples?.nom ?? '—'}`);
        lines.push(`  Âmes    : ${r.donnees?.total ?? '—'}`);
        lines.push('');
    });
    if (!(props.rapports.data || []).length) lines.push('Aucun rapport mensuel.');
    downloadFile('rapports-mensuels-liste.txt', lines.join('\n'));
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
    <Head title="Rapports mensuels" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h1 class="text-lg font-semibold text-slate-800">Rapports mensuels</h1>
                <div class="flex gap-2">
                    <button @click="exportTxt" type="button" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        📥 Exporter la liste en TXT
                    </button>
                    <Link :href="route('rapport-mensuel.create')" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
                        + Nouveau rapport
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-4">
            <div v-if="!rapports.data?.length" class="rounded-xl bg-white p-12 text-center shadow-sm ring-1 ring-slate-200">
                <p class="text-lg text-slate-400">Aucun rapport mensuel.</p>
                <p class="mt-2 text-sm text-slate-400">Cliquez sur "+ Nouveau rapport" pour generer votre premier rapport.</p>
            </div>

            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="divide-y divide-slate-100">
                    <Link
                        v-for="r in rapports.data" :key="r.id"
                        :href="route('rapport-mensuel.show', r.id)"
                        class="flex items-center justify-between px-5 py-4 transition hover:bg-slate-50"
                    >
                        <div class="flex items-center gap-4">
                            <div class="flex h-11 w-11 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-700">
                                {{ r.faiseur?.prenom?.[0] }}{{ r.faiseur?.nom?.[0] }}
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-800">{{ moisNom(r.mois) }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ r.faiseur?.prenom }} {{ r.faiseur?.nom }}
                                    <span v-if="r.famille_disciples"> &middot; FD {{ r.famille_disciples.nom }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-bold text-slate-800">{{ r.donnees?.total ?? '—' }}</p>
                            <p class="text-xs text-slate-500">ames</p>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
