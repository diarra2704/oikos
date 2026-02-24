<script setup lang="ts">
</script>

<template>
    <div class="flex min-h-screen min-h-[100dvh] flex-col bg-gradient-to-br from-slate-50 via-blue-50/40 to-indigo-100/50 px-4 py-6 pb-[env(safe-area-inset-bottom)] pt-[env(safe-area-inset-top)] sm:px-6">
        <!-- Motif de fond -->
        <div class="pointer-events-none fixed inset-0 overflow-hidden">
            <div class="absolute -left-40 -top-40 h-80 w-80 rounded-full bg-blue-300/25 blur-3xl" />
            <div class="absolute -bottom-40 -right-40 h-80 w-80 rounded-full bg-indigo-300/25 blur-3xl" />
            <div class="absolute left-1/2 top-1/2 h-64 w-64 -translate-x-1/2 -translate-y-1/2 rounded-full bg-violet-200/15 blur-3xl" />
            <!-- Grille légère -->
            <div class="absolute inset-0 bg-[linear-gradient(to_right,rgb(148_163_184/0.04)_1px,transparent_1px),linear-gradient(to_bottom,rgb(148_163_184/0.04)_1px,transparent_1px)] bg-[size:24px_24px]" />
        </div>
        <div class="relative mx-auto flex w-full max-w-md flex-1 flex-col justify-center">
            <slot />
        </div>
    </div>
</template>
