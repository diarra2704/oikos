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
            <!-- Carte principale -->
            <div class="relative rounded-2xl bg-white/95 p-6 shadow-2xl shadow-slate-300/30 ring-1 ring-slate-200/80 backdrop-blur-md transition-all sm:p-8">
                <!-- Ligne d'accent en haut -->
                <div class="absolute inset-x-0 top-0 h-1 rounded-t-2xl bg-gradient-to-r from-blue-500 via-indigo-500 to-violet-500" />

                <!-- En-tête -->
                <header class="flex flex-col items-center text-center">
                    <div class="flex items-center justify-center drop-shadow-lg">
                        <img src="/images/logo.png" alt="Oikos" class="h-16 w-16 object-contain sm:h-20 sm:w-20" />
                    </div>
                    <h1 class="mt-5 text-2xl font-bold tracking-tight text-slate-800 sm:text-3xl">Oikos</h1>
                    <p class="mx-auto mt-2 max-w-[18rem] text-sm leading-relaxed text-slate-500 sm:max-w-[22rem] sm:text-[0.9375rem]">
                        Système Intégré de Gestion des Âmes à travers les Familles de Disciples
                    </p>
                </header>

                <div v-if="status" class="mt-5 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800 ring-1 ring-emerald-200/50">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="mt-6 space-y-5 sm:mt-7">
                    <div>
                        <InputLabel for="email" value="Email" class="text-slate-700" />
                        <TextInput
                            id="email"
                            type="email"
                            class="mt-1.5 block w-full rounded-xl border-slate-200 bg-slate-50/50 py-3.5 text-base transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-blue-500 sm:py-3"
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
                            class="mt-1.5 block w-full rounded-xl border-slate-200 bg-slate-50/50 py-3.5 text-base transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-blue-500 sm:py-3"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                        />
                        <InputError class="mt-1.5" :message="form.errors.password" />
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <label for="remember" class="flex min-h-[44px] cursor-pointer items-center gap-3 sm:min-h-0">
                            <Checkbox id="remember" name="remember" v-model:checked="form.remember" class="h-5 w-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                            <span class="text-sm text-slate-600">Se souvenir de moi</span>
                        </label>
                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="min-h-[44px] flex items-center text-sm font-medium text-blue-600 hover:text-blue-700 sm:min-h-0"
                        >
                            Mot de passe oublié ?
                        </Link>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="min-h-[48px] w-full rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 py-3.5 text-base font-semibold text-white shadow-lg shadow-blue-500/25 transition hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 active:from-blue-800 active:to-indigo-800 disabled:opacity-50 sm:min-h-[44px] sm:py-2.5 sm:text-sm"
                    >
                        {{ form.processing ? 'Connexion...' : 'Se connecter' }}
                    </button>
                </form>
            </div>

            <div class="mt-8 flex flex-col items-center gap-3 sm:mt-10">
                <div class="h-px w-12 rounded-full bg-slate-300/80" />
                <p class="text-center text-sm font-medium text-slate-500">Impact Centre Chrétien Yaoundé</p>
            </div>
        </div>
    </GuestLayout>
</template>
