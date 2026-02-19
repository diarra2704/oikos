<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

defineProps<{ classement: any[] }>();

const user = usePage().props.auth.user as any;

function medal(index: number): string {
    if (index === 0) return '🥇';
    if (index === 1) return '🥈';
    if (index === 2) return '🥉';
    return `${index + 1}`;
}

const roleLabels: Record<string, string> = {
    admin: 'Admin',
    superviseur: 'Superviseur',
    leader_cellule: 'Leader',
    faiseur: 'Faiseur',
};
</script>

<template>
    <Head title="Tableau d'honneur" />
    <AuthenticatedLayout>
        <template #header><h1 class="text-lg font-semibold text-slate-800">Tableau d'honneur</h1></template>

        <div class="mx-auto max-w-lg space-y-4">
            <!-- Top 3 -->
            <div v-if="classement.length >= 3" class="flex items-end justify-center gap-3">
                <!-- 2e place -->
                <div class="flex flex-col items-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-lg font-bold text-slate-700 ring-2 ring-slate-300">
                        {{ classement[1]?.prenom?.[0] }}{{ classement[1]?.nom?.[0] }}
                    </div>
                    <p class="mt-1 text-xs font-medium text-slate-700">{{ classement[1]?.prenom }}</p>
                    <p class="text-lg font-bold text-slate-500">🥈</p>
                    <p class="text-xs text-slate-500">{{ classement[1]?.total_points }} pts</p>
                </div>
                <!-- 1ère place -->
                <div class="flex flex-col items-center -mt-4">
                    <div class="flex h-18 w-18 items-center justify-center rounded-full bg-amber-100 text-xl font-bold text-amber-800 ring-4 ring-amber-400" style="width:4.5rem;height:4.5rem;">
                        {{ classement[0]?.prenom?.[0] }}{{ classement[0]?.nom?.[0] }}
                    </div>
                    <p class="mt-1 text-sm font-bold text-slate-800">{{ classement[0]?.prenom }}</p>
                    <p class="text-2xl font-bold">🥇</p>
                    <p class="text-sm font-bold text-amber-600">{{ classement[0]?.total_points }} pts</p>
                </div>
                <!-- 3e place -->
                <div class="flex flex-col items-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-amber-50 text-lg font-bold text-amber-700 ring-2 ring-amber-200">
                        {{ classement[2]?.prenom?.[0] }}{{ classement[2]?.nom?.[0] }}
                    </div>
                    <p class="mt-1 text-xs font-medium text-slate-700">{{ classement[2]?.prenom }}</p>
                    <p class="text-lg font-bold text-amber-600">🥉</p>
                    <p class="text-xs text-slate-500">{{ classement[2]?.total_points }} pts</p>
                </div>
            </div>

            <!-- Classement complet -->
            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="divide-y divide-slate-100">
                    <div
                        v-for="(u, index) in classement" :key="u.id"
                        class="flex items-center gap-3 px-4 py-3"
                        :class="u.id === user?.id ? 'bg-blue-50' : ''"
                    >
                        <span class="w-8 text-center text-sm font-bold" :class="index < 3 ? 'text-xl' : 'text-slate-400'">
                            {{ medal(index) }}
                        </span>
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">
                            {{ u.prenom?.[0] }}{{ u.nom?.[0] }}
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-slate-800">{{ u.prenom }} {{ u.nom }}</p>
                            <div class="flex items-center gap-2 text-xs text-slate-500">
                                <span class="capitalize">{{ roleLabels[u.role] }}</span>
                                <span v-if="u.palier" class="rounded-full px-1.5 py-0.5 text-xs font-semibold text-white" :style="{ backgroundColor: u.palier.couleur }">
                                    {{ u.palier.label }}
                                </span>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-blue-600">{{ u.total_points }}</p>
                            <p class="text-xs text-slate-400">{{ u.badges_count }} badges</p>
                        </div>
                    </div>
                </div>
                <div v-if="!classement.length" class="p-8 text-center text-sm text-slate-400">
                    Aucun classement disponible.
                </div>
            </div>

            <div class="text-center">
                <Link :href="route('badges.profil')" class="text-sm font-medium text-blue-600 hover:text-blue-700">
                    Voir mon profil &rarr;
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
