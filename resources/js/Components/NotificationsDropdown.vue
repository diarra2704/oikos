<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';

interface NotificationItem {
    type: string;
    label: string;
    url: string;
    count: number;
}

const props = defineProps<{
    items: NotificationItem[];
    total: number;
}>();

const open = ref(false);

const close = () => { open.value = false; };

const closeOnEscape = (e: KeyboardEvent) => {
    if (open.value && e.key === 'Escape') close();
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));

const hasNotifications = computed(() => props.total > 0);
</script>

<template>
    <div class="relative">
        <button
            type="button"
            class="relative rounded-lg p-2 text-slate-600 transition hover:bg-slate-100 hover:text-slate-800"
            aria-label="Notifications"
            @click="open = !open"
        >
            <span class="text-xl">🔔</span>
            <span
                v-if="hasNotifications"
                class="absolute -right-0.5 -top-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-amber-500 px-1.5 text-xs font-bold text-white"
            >
                {{ total > 99 ? '99+' : total }}
            </span>
        </button>

        <div
            v-show="open"
            class="fixed inset-0 z-40"
            aria-hidden="true"
            @click="close"
        />

        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-show="open"
                class="absolute end-0 z-50 mt-2 w-72 origin-top-right rounded-xl border border-slate-200 bg-white py-2 shadow-lg"
                @click.stop
            >
                <div class="border-b border-slate-100 px-4 py-2">
                    <h3 class="text-sm font-semibold text-slate-800">Notifications</h3>
                </div>
                <div v-if="!items.length" class="px-4 py-6 text-center text-sm text-slate-500">
                    Aucune notification
                </div>
                <ul v-else class="max-h-80 overflow-y-auto">
                    <li v-for="(item, index) in items" :key="index">
                        <Link
                            :href="item.url"
                            class="flex flex-col gap-0.5 border-b border-slate-50 px-4 py-3 text-left transition hover:bg-slate-50 last:border-0"
                            @click="close"
                        >
                            <span class="text-sm font-medium text-slate-800">{{ item.label }}</span>
                        </Link>
                    </li>
                </ul>
            </div>
        </Transition>
    </div>
</template>
