<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    contenu: '',
});

function submit() {
    form.post(route('temoignages.store'));
}
</script>

<template>
    <Head title="Partager un témoignage" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('temoignages.index')" class="text-slate-400 hover:text-slate-600">&larr;</Link>
                <h1 class="text-lg font-semibold text-slate-800">Partager un témoignage</h1>
            </div>
        </template>

        <form @submit.prevent="submit" class="mx-auto max-w-lg space-y-5">
            <div class="rounded-xl bg-purple-50 p-4 ring-1 ring-purple-200">
                <p class="text-sm font-medium text-purple-800">Partagez ce que Dieu a fait dans votre vie</p>
                <p class="mt-1 text-xs text-purple-600">Votre témoignage sera validé par votre superviseur avant publication.</p>
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <label class="mb-2 block text-sm font-medium text-slate-700">Votre témoignage</label>
                <textarea
                    v-model="form.contenu"
                    rows="8"
                    required
                    minlength="20"
                    placeholder="Racontez comment Dieu a agi dans votre vie..."
                    class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm leading-relaxed focus:border-blue-500 focus:ring-blue-500"
                ></textarea>
                <p v-if="form.errors.contenu" class="mt-1 text-xs text-red-500">{{ form.errors.contenu }}</p>
                <p class="mt-2 text-xs text-slate-400">Minimum 20 caractères</p>
            </div>

            <button
                type="submit"
                :disabled="form.processing || form.contenu.length < 20"
                class="w-full rounded-xl bg-purple-600 py-3.5 text-sm font-semibold text-white shadow-lg transition hover:bg-purple-700 disabled:opacity-50 active:scale-[0.98]"
            >
                {{ form.processing ? 'Envoi...' : 'Soumettre mon témoignage' }}
            </button>
        </form>
    </AuthenticatedLayout>
</template>
