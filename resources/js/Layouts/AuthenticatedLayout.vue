<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import GlobalSearch from '@/Components/GlobalSearch.vue';
import NotificationsDropdown from '@/Components/NotificationsDropdown.vue';

const page = usePage();
const user = computed(() => page.props.auth.user as any);
const flash = computed(() => (page.props as any).flash);
const notifications = computed(() => (page.props as any).notifications ?? { items: [], total: 0 });
const showFlash = ref(true);
const mobileMenuOpen = ref(false);

const navigation = computed(() => {
    const role = user.value?.role;
    const items = [
        { name: 'Tableau de bord', href: 'dashboard', icon: '🏠', roles: ['admin', 'superviseur', 'leader_cellule', 'faiseur'] },
        { name: 'Familles de Disciples', href: 'fd.index', icon: '👥', roles: ['admin', 'superviseur'] },
        { name: 'Membres', href: 'membres.index', icon: '📇', roles: ['admin', 'superviseur', 'leader_cellule', 'faiseur'] },
        { name: 'Pointer', href: 'presences.create', icon: '✅', roles: ['admin', 'superviseur', 'leader_cellule', 'faiseur'] },
        { name: 'Présences', href: 'presences.index', icon: '📋', roles: ['admin', 'superviseur', 'leader_cellule', 'faiseur'] },
        { name: 'Rapport Culte', href: 'rapport-culte', icon: '⛪', roles: ['admin', 'superviseur'] },
        { name: 'Rapport Mensuel', href: 'rapport-mensuel.index', icon: '📝', roles: ['admin', 'superviseur', 'leader_cellule', 'faiseur'] },
        { name: 'Rapports', href: 'rapports.index', icon: '📋', roles: ['admin', 'superviseur', 'leader_cellule', 'faiseur'] },
        { name: 'Transferts', href: 'transferts.index', icon: '🔄', roles: ['admin', 'superviseur'] },
        { name: 'Envoi SMS', href: 'sms.index', icon: '📱', roles: ['admin', 'superviseur'] },
        { name: 'KPI', href: 'kpi.index', icon: '📊', roles: ['admin', 'superviseur'] },
        { name: 'Témoignages', href: 'temoignages.index', icon: '✨', roles: ['admin', 'superviseur', 'leader_cellule', 'faiseur'] },
        { name: 'Tableau d\'honneur', href: 'honneur', icon: '🏆', roles: ['admin', 'superviseur', 'leader_cellule', 'faiseur'] },
        { name: 'Anniversaires', href: 'anniversaires.index', icon: '🎂', roles: ['admin', 'superviseur', 'leader_cellule', 'faiseur'] },
        { name: 'Mes rappels', href: 'rappels.index', icon: '⏰', roles: ['faiseur'] },
        { name: 'Invitations au culte', href: 'invitations.index', icon: '🎫', roles: ['admin', 'superviseur', 'leader_cellule', 'faiseur'] },
        { name: 'Historique', href: 'historique.index', icon: '📜', roles: ['admin', 'superviseur'] },
        { name: 'Utilisateurs', href: 'users.index', icon: '👤', roles: ['admin'] },
        { name: 'Paramètrage', href: 'parametrage.index', icon: '⚙️', roles: ['admin'] },
    ];
    return items.filter(item => item.roles.includes(role));
});

const mobileNav = computed(() => {
    const role = user.value?.role;
    if (role === 'admin' || role === 'superviseur') {
        return [
            { name: 'Accueil', href: 'dashboard', icon: '🏠' },
            { name: 'Membres', href: 'membres.index', icon: '📇' },
            { name: 'Pointer', href: 'presences.create', icon: '✅' },
            { name: 'KPI', href: 'kpi.index', icon: '📊' },
            { name: 'Plus', href: '', icon: '☰', openMenu: true },
        ];
    }
    return [
        { name: 'Accueil', href: 'dashboard', icon: '🏠' },
        { name: 'Pointer', href: 'presences.create', icon: '✅' },
        { name: 'Mensuel', href: 'rapport-mensuel.index', icon: '📝' },
        { name: 'Plus', href: '', icon: '☰', openMenu: true },
    ];
});

function closeMobileMenu() {
    mobileMenuOpen.value = false;
}

function isActive(routeName: string): boolean {
    try { return route().current(routeName) ?? false; } catch { return false; }
}
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <!-- ════════ SIDEBAR DESKTOP ════════ -->
        <aside class="sidebar fixed inset-y-0 left-0 z-30 hidden w-64 flex-col bg-gradient-to-b from-blue-900 via-blue-900 to-indigo-950 shadow-xl lg:flex">
            <div class="flex h-16 flex-shrink-0 items-center border-b border-white/10 px-5">
                <Link :href="route('dashboard')" class="flex items-center gap-3">
                    <img src="/images/logo.png" alt="Oikos" class="h-10 w-10 object-contain" />
                    <span class="text-lg font-bold tracking-tight text-white">Oikos</span>
                                </Link>
                            </div>

            <nav class="sidebar-nav min-h-0 flex-1 space-y-1 overflow-x-hidden px-3 py-4" aria-label="Navigation principale">
                <Link
                    v-for="item in navigation" :key="item.name"
                    :href="route(item.href)"
                    class="sidebar-link flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200"
                    :class="isActive(item.href)
                        ? 'bg-white/20 text-white shadow-md ring-1 ring-white/10'
                        : 'text-blue-200/90 hover:bg-white/10 hover:text-white hover:shadow-sm'"
                >
                    <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-white/10 text-base">{{ item.icon }}</span>
                    <span class="truncate">{{ item.name }}</span>
                </Link>
            </nav>

            <div class="flex flex-shrink-0 border-t border-white/10 bg-black/10 p-4">
                <div class="flex w-full items-center gap-3 rounded-xl bg-white/5 px-3 py-2.5 ring-1 ring-white/10">
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 text-sm font-bold text-white shadow">
                        {{ user?.prenom?.[0] }}{{ user?.nom?.[0] }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-white">{{ user?.prenom }} {{ user?.nom }}</p>
                        <p class="truncate text-xs text-blue-300/80 capitalize">{{ user?.role?.replace('_', ' ') }}</p>
                    </div>
                            </div>
                        </div>
        </aside>

        <!-- ════════ TOP BAR ════════ -->
        <header class="fixed top-0 right-0 left-0 z-20 border-b border-slate-200 bg-white lg:left-64">
            <div class="flex h-14 items-center justify-between px-4 lg:px-6">
                <div class="flex items-center gap-3 lg:hidden">
                    <img src="/images/logo.png" alt="Oikos" class="h-8 w-8 object-contain" />
                    <span class="text-lg font-bold text-slate-800">Oikos</span>
                </div>
                <div class="hidden flex-1 lg:block">
                    <slot name="header"><h1 class="text-lg font-semibold text-slate-800">Tableau de bord</h1></slot>
                </div>
                <div class="hidden min-w-0 flex-1 px-2 sm:block lg:flex-none lg:px-0">
                    <GlobalSearch />
                </div>
                <div class="flex items-center gap-1">
                    <NotificationsDropdown :items="notifications.items" :total="notifications.total" />
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                            <button class="flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm text-slate-600 transition hover:bg-slate-100">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">
                                    {{ user?.prenom?.[0] }}{{ user?.nom?.[0] }}
                                </div>
                                <span class="hidden md:inline">{{ user?.prenom }}</span>
                                            </button>
                                    </template>
                                    <template #content>
                            <DropdownLink :href="route('profile.edit')">Mon profil</DropdownLink>
                            <DropdownLink :href="route('logout')" method="post" as="button">Déconnexion</DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>
        </header>

        <!-- ════════ FLASH MESSAGES ════════ -->
        <div v-if="flash?.success && showFlash" class="fixed top-16 right-4 left-4 z-50 lg:left-auto lg:w-96">
            <div class="flex items-center gap-3 rounded-xl bg-emerald-50 p-4 shadow-lg ring-1 ring-emerald-200">
                <span class="text-lg">✅</span>
                <p class="flex-1 text-sm font-medium text-emerald-800">{{ flash.success }}</p>
                <button @click="showFlash = false" class="text-emerald-400 hover:text-emerald-600">&times;</button>
                    </div>
                </div>

        <!-- ════════ MAIN CONTENT ════════ -->
        <main class="pt-14 pb-20 lg:pb-6 lg:pl-64">
            <div class="mx-auto max-w-7xl px-4 py-4 lg:px-6 lg:py-6">
                <slot />
            </div>
        </main>

        <!-- ════════ BOTTOM NAV MOBILE ════════ -->
        <nav class="fixed bottom-0 left-0 right-0 z-30 border-t border-slate-200 bg-white safe-area-bottom lg:hidden" aria-label="Navigation mobile">
            <div class="flex h-16 items-center justify-around">
                <template v-for="item in mobileNav" :key="item.name">
                    <button
                        v-if="item.openMenu"
                        type="button"
                        class="flex flex-1 flex-col items-center gap-0.5 py-1 text-xs font-medium transition-colors text-slate-400 hover:text-blue-600"
                        @click="mobileMenuOpen = true"
                        aria-label="Plus"
                    >
                        <span class="text-xl">{{ item.icon }}</span>
                        {{ item.name }}
                    </button>
                    <Link
                        v-else
                        :href="route(item.href)"
                        class="flex flex-1 flex-col items-center gap-0.5 py-1 text-xs font-medium transition-colors"
                        :class="isActive(item.href) ? 'text-blue-600' : 'text-slate-400'"
                    >
                        <span class="text-xl">{{ item.icon }}</span>
                        {{ item.name }}
                    </Link>
                </template>
            </div>
        </nav>

        <!-- ════════ DRAWER MENU MOBILE (tous les modules) ════════ -->
        <Teleport to="body">
            <Transition name="drawer">
                <div
                    v-show="mobileMenuOpen"
                    class="fixed inset-0 z-[100] lg:hidden"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Menu"
                >
                    <div class="absolute inset-0 bg-black/50" @click="closeMobileMenu" />
                    <div class="absolute bottom-0 left-0 right-0 max-h-[85vh] overflow-y-auto rounded-t-2xl bg-white shadow-2xl safe-area-bottom">
                        <div class="sticky top-0 flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3">
                            <h2 class="text-lg font-semibold text-slate-800">Menu</h2>
                            <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" @click="closeMobileMenu" aria-label="Fermer le menu">✕</button>
                    </div>
                        <nav class="grid gap-0 py-2" aria-label="Tous les modules">
                            <Link
                                v-for="item in navigation"
                                :key="item.name"
                                :href="route(item.href)"
                                class="flex items-center gap-3 px-4 py-3 text-left text-slate-700 hover:bg-slate-50"
                                :class="isActive(item.href) ? 'bg-blue-50 text-blue-700' : ''"
                                @click="closeMobileMenu"
                            >
                                <span class="text-xl">{{ item.icon }}</span>
                                <span class="font-medium">{{ item.name }}</span>
                            </Link>
                            <Link
                                :href="route('badges.profil')"
                                class="flex items-center gap-3 px-4 py-3 text-left text-slate-700 hover:bg-slate-50"
                                :class="isActive('badges.profil') ? 'bg-blue-50 text-blue-700' : ''"
                                @click="closeMobileMenu"
                            >
                                <span class="text-xl">🏅</span>
                                <span class="font-medium">Mon profil badges</span>
                            </Link>
                        </nav>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
.safe-area-bottom { padding-bottom: env(safe-area-inset-bottom); }

/* Sidebar scrollbar – toujours visible */
.sidebar-nav {
  overflow-y: scroll;
  scrollbar-width: thin;
  scrollbar-gutter: stable;
  scrollbar-color: rgba(255, 255, 255, 0.4) rgba(255, 255, 255, 0.08);
}
.sidebar-nav::-webkit-scrollbar {
  width: 8px;
}
.sidebar-nav::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.08);
  border-radius: 4px;
}
.sidebar-nav::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.35);
  border-radius: 4px;
}
.sidebar-nav::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.5);
}
.sidebar-nav::-webkit-scrollbar-thumb:active {
  background: rgba(255, 255, 255, 0.6);
}
</style>

<style>
/* Transition drawer mobile (non-scoped pour Teleport) */
.drawer-enter-active,
.drawer-leave-active {
  transition: opacity 0.2s ease;
}
.drawer-enter-active .absolute.bottom-0,
.drawer-leave-active .absolute.bottom-0 {
  transition: transform 0.25s ease;
}
.drawer-enter-from,
.drawer-leave-to {
  opacity: 0;
}
.drawer-enter-from .absolute.bottom-0,
.drawer-leave-to .absolute.bottom-0 {
  transform: translateY(100%);
}
</style>
