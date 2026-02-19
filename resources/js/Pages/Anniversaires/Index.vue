<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps<{
    naissance: { id: number; label: string; date: string; jour: number; type: string }[];
    conversion: { id: number; label: string; date: string; jour: number; type: string }[];
    mois: number;
    annee: number;
    mois_label: string;
    mois_options: { value: number; label: string }[];
}>();

const moisSelect = ref(props.mois);
const anneeSelect = ref(props.annee);

function applyMonth() {
    router.get(route('anniversaires.index'), { mois: moisSelect.value, annee: anneeSelect.value }, { preserveState: false });
}

const anneesOptions = computed(() => {
    const y = new Date().getFullYear();
    return [y - 1, y, y + 1];
});

function exportTxt() {
    const lines: string[] = [
        `ANNIVERSAIRES — ${props.mois_label} ${props.annee}`,
        '='.repeat(50),
        '',
        '--- Anniversaires de naissance ---',
        ...(props.naissance.length ? props.naissance.map((item: any) => `  ${item.label.padEnd(35)} Jour ${item.jour}`) : ['  Aucun ce mois-ci.']),
        '',
        '--- Anniversaires de conversion ---',
        ...(props.conversion.length ? props.conversion.map((item: any) => `  ${item.label.padEnd(35)} Jour ${item.jour}`) : ['  Aucun ce mois-ci.']),
    ];
    const filename = `anniversaires-${props.annee}-${String(props.mois).padStart(2, '0')}.txt`;
    downloadFile(filename, lines.join('\n'));
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
    <Head title="Anniversaires" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h1 class="text-lg font-semibold text-slate-800">Anniversaires</h1>
                <button @click="exportTxt" type="button" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    📥 Exporter en TXT
                </button>
            </div>
        </template>

        <div class="space-y-6">
            <div class="flex flex-wrap items-center gap-3">
                <select
                    v-model="moisSelect"
                    class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                    @change="applyMonth"
                >
                    <option v-for="opt in mois_options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
                <select
                    v-model="anneeSelect"
                    class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                    @change="applyMonth"
                >
                    <option v-for="y in anneesOptions" :key="y" :value="y">{{ y }}</option>
                </select>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                        <h2 class="text-sm font-semibold text-slate-800">Anniversaires de naissance · {{ mois_label }}</h2>
                    </div>
                    <ul class="divide-y divide-slate-100">
                        <li v-for="item in naissance" :key="'n-' + item.id" class="flex items-center justify-between px-4 py-3">
                            <Link :href="route('membres.show', item.id)" class="font-medium text-slate-800 hover:text-blue-600">
                                {{ item.label }}
                            </Link>
                            <span class="text-sm text-slate-500">Jour {{ item.jour }}</span>
                        </li>
                        <li v-if="!naissance.length" class="px-4 py-6 text-center text-sm text-slate-500">
                            Aucun anniversaire de naissance ce mois-ci.
                        </li>
                    </ul>
                </div>

                <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                        <h2 class="text-sm font-semibold text-slate-800">Anniversaires de conversion · {{ mois_label }}</h2>
                    </div>
                    <ul class="divide-y divide-slate-100">
                        <li v-for="item in conversion" :key="'c-' + item.id" class="flex items-center justify-between px-4 py-3">
                            <Link :href="route('membres.show', item.id)" class="font-medium text-slate-800 hover:text-blue-600">
                                {{ item.label }}
                            </Link>
                            <span class="text-sm text-slate-500">Jour {{ item.jour }}</span>
                        </li>
                        <li v-if="!conversion.length" class="px-4 py-6 text-center text-sm text-slate-500">
                            Aucun anniversaire de conversion ce mois-ci.
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
