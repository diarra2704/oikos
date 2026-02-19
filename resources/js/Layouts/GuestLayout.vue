<script setup lang="ts">
</script>

<template>
    <div class="flex min-h-screen min-h-[100dvh] flex-col bg-slate-100 px-4 py-6 pb-[env(safe-area-inset-bottom)] pt-[env(safe-area-inset-top)] sm:px-6">
        <div class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center">
            <slot />
        </div>
    </div>
</template>
