<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Connexion" />

        <div class="w-full">
            <!-- En-tête compact -->
            <header class="flex flex-col items-center text-center">
                <img src="/images/logo.png" alt="Oikos" class="h-12 w-12 object-contain sm:h-14 sm:w-14" />
                <h1 class="mt-2 text-lg font-semibold text-slate-800 sm:text-xl">Oikos</h1>
                <p class="mt-0.5 text-sm text-slate-500">Suivi des âmes et familles de disciples</p>
            </header>

            <div v-if="status" class="mt-5 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="mt-5 space-y-4 sm:mt-6">
                <div>
                    <InputLabel for="email" value="Email" class="text-slate-700" />
                    <TextInput
                        id="email"
                        type="email"
                        class="mt-1.5 block w-full rounded-xl border-slate-300 bg-white py-3.5 text-base focus:border-slate-400 focus:ring-slate-400 sm:py-3"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="votre@email.com"
                    />
                    <InputError class="mt-1.5" :message="form.errors.email" />
                </div>

                <div>
                    <InputLabel for="password" value="Mot de passe" class="text-slate-700" />
                    <TextInput
                        id="password"
                        type="password"
                        class="mt-1.5 block w-full rounded-xl border-slate-300 bg-white py-3.5 text-base focus:border-slate-400 focus:ring-slate-400 sm:py-3"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                    />
                    <InputError class="mt-1.5" :message="form.errors.password" />
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <label for="remember" class="flex min-h-[44px] cursor-pointer items-center gap-3 sm:min-h-0">
                        <Checkbox id="remember" name="remember" v-model:checked="form.remember" class="h-5 w-5 rounded border-slate-300" />
                        <span class="text-sm text-slate-600">Se souvenir de moi</span>
                    </label>
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="min-h-[44px] flex items-center text-sm text-slate-600 hover:text-slate-800 sm:min-h-0"
                    >
                        Mot de passe oublié ?
                    </Link>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="min-h-[48px] w-full rounded-xl bg-slate-800 py-3.5 text-base font-medium text-white hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 active:bg-slate-900 disabled:opacity-50 sm:min-h-[44px] sm:py-2.5 sm:text-sm"
                >
                    {{ form.processing ? 'Connexion...' : 'Se connecter' }}
                </button>

                <p class="mt-5 text-center text-sm text-slate-500">
                    Pas encore de compte ?
                    <Link :href="route('register')" class="font-medium text-slate-700 hover:text-slate-900">Créer un compte</Link>
                </p>
            </form>

            <p class="mt-6 text-center text-xs text-slate-400 sm:mt-8">Impact Centre Chrétien</p>
        </div>
    </GuestLayout>
</template>
