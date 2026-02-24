<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    user: { id: number; nom: string; prenom: string; email: string };
}>();

const form = useForm({
    password: '',
    password_confirmation: '',
});

function submit() {
    form.put(route('users.reset-password.update', props.user.id));
}
</script>

<template>
    <Head title="Réinitialiser le mot de passe" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('users.index')" class="text-slate-400 hover:text-slate-600">&larr;</Link>
                <h1 class="text-lg font-semibold text-slate-800">Réinitialiser le mot de passe</h1>
            </div>
        </template>

        <div class="mx-auto max-w-md rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <p class="mb-4 text-sm text-slate-600">
                Nouveau mot de passe pour <strong>{{ props.user.prenom }} {{ props.user.nom }}</strong> ({{ props.user.email }}).
            </p>
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Nouveau mot de passe</label>
                    <input
                        v-model="form.password"
                        type="password"
                        required
                        minlength="8"
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                        autocomplete="new-password"
                    />
                    <p class="mt-1 text-xs text-slate-500">Minimum 8 caractères.</p>
                    <p v-if="form.errors.password" class="mt-1 text-xs text-red-500">{{ form.errors.password }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Confirmer le mot de passe</label>
                    <input
                        v-model="form.password_confirmation"
                        type="password"
                        required
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                        autocomplete="new-password"
                    />
                    <p v-if="form.errors.password_confirmation" class="mt-1 text-xs text-red-500">{{ form.errors.password_confirmation }}</p>
                </div>
                <div class="flex gap-3 pt-2">
                    <Link :href="route('users.index')" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Annuler
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Enregistrement...' : 'Réinitialiser le mot de passe' }}
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
