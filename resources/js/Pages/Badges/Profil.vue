<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const props = defineProps<{
    badges: any[];
    progression: any;
    pointsRecents: any[];
    nouveauxBadges: any[];
}>();

const user = usePage().props.auth.user as any;
</script>

<template>
    <Head title="Mon profil - Badges" />
    <AuthenticatedLayout>
        <template #header><h1 class="text-lg font-semibold text-slate-800">Mon Profil Gamification</h1></template>

        <div class="mx-auto max-w-lg space-y-5">
            <!-- Notification nouveau badge -->
            <div v-if="nouveauxBadges.length" class="rounded-xl bg-amber-50 p-4 ring-1 ring-amber-200">
                <p class="text-sm font-bold text-amber-800">Nouveau(x) badge(s) obtenu(s) !</p>
                <div v-for="b in nouveauxBadges" :key="b.id" class="mt-2 flex items-center gap-2">
                    <span class="text-2xl">{{ b.icone }}</span>
                    <span class="text-sm font-medium text-amber-700">{{ b.nom }}</span>
                </div>
            </div>

            <!-- Points & Palier -->
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div class="text-center">
                    <p class="text-4xl font-bold text-blue-600">{{ progression.total }}</p>
                    <p class="mt-1 text-sm text-slate-500">points</p>
                </div>

                <div v-if="progression.palier_actuel" class="mt-4 text-center">
                    <span class="inline-block rounded-full px-4 py-1.5 text-sm font-semibold text-white" :style="{ backgroundColor: progression.palier_actuel.couleur }">
                        {{ progression.palier_actuel.label }}
                    </span>
                </div>

                <div v-if="progression.prochain_palier" class="mt-5">
                    <div class="flex justify-between text-xs text-slate-500">
                        <span>Progression</span>
                        <span>{{ progression.total }} / {{ progression.prochain_palier.seuil }} pts</span>
                    </div>
                    <div class="mt-2 h-3 overflow-hidden rounded-full bg-slate-200">
                        <div class="h-full rounded-full bg-blue-600 transition-all" :style="{ width: `${Math.min(progression.progression, 100)}%` }"></div>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Prochain palier : <span class="font-semibold" :style="{ color: progression.prochain_palier.couleur }">{{ progression.prochain_palier.label }}</span></p>
                </div>
            </div>

            <!-- Badges -->
            <div>
                <h2 class="mb-3 text-base font-semibold text-slate-700">Mes Badges</h2>
                <div class="grid grid-cols-2 gap-3">
                    <div
                        v-for="badge in badges" :key="badge.id"
                        class="rounded-xl p-4 text-center transition"
                        :class="badge.obtenu
                            ? 'bg-white shadow-sm ring-1 ring-slate-200'
                            : 'bg-slate-100 opacity-50 ring-1 ring-slate-200'"
                    >
                        <span class="text-3xl">{{ badge.icone }}</span>
                        <p class="mt-2 text-sm font-semibold" :class="badge.obtenu ? 'text-slate-800' : 'text-slate-400'">
                            {{ badge.nom }}
                        </p>
                        <p class="mt-1 text-xs" :class="badge.obtenu ? 'text-emerald-600 font-medium' : 'text-slate-400'">
                            {{ badge.obtenu ? 'Obtenu ✓' : badge.description }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Historique points -->
            <div v-if="pointsRecents.length">
                <h2 class="mb-3 text-base font-semibold text-slate-700">Points récents</h2>
                <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="divide-y divide-slate-100">
                        <div v-for="p in pointsRecents" :key="p.id" class="flex items-center justify-between px-4 py-3">
                            <div>
                                <p class="text-sm text-slate-700">{{ p.description || p.action }}</p>
                                <p class="text-xs text-slate-400">{{ new Date(p.created_at).toLocaleDateString('fr-FR') }}</p>
                            </div>
                            <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-700">
                                +{{ p.points }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liens rapides -->
            <div class="flex gap-3">
                <Link :href="route('honneur')" class="flex-1 rounded-xl border border-slate-300 bg-white py-3 text-center text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                    Tableau d'honneur
                </Link>
                <Link :href="route('temoignages.create')" class="flex-1 rounded-xl bg-purple-600 py-3 text-center text-sm font-medium text-white transition hover:bg-purple-700">
                    Partager un témoignage
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
