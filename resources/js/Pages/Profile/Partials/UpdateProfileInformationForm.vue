<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm, usePage } from '@inertiajs/vue3';

defineProps<{
    mustVerifyEmail?: Boolean;
    status?: String;
}>();

const user = usePage().props.auth.user as any;

const form = useForm({
    nom: user.nom,
    prenom: user.prenom,
    email: user.email || '',
    telephone: user.telephone,
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Informations du profil
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                Mettez à jour vos informations personnelles.
            </p>
            <p v-if="status" class="mt-2 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">
                {{ status }}
            </p>
        </header>

        <form
            @submit.prevent="form.patch(route('profile.update'))"
            class="mt-6 space-y-6"
        >
            <div>
                <InputLabel for="prenom" value="Prénom" />
                <TextInput id="prenom" type="text" class="mt-1 block w-full" v-model="form.prenom" required autofocus />
                <InputError class="mt-2" :message="form.errors.prenom" />
            </div>

            <div>
                <InputLabel for="nom" value="Nom" />
                <TextInput id="nom" type="text" class="mt-1 block w-full" v-model="form.nom" required />
                <InputError class="mt-2" :message="form.errors.nom" />
            </div>

            <div>
                <InputLabel for="telephone" value="Téléphone" />
                <TextInput id="telephone" type="tel" class="mt-1 block w-full" v-model="form.telephone" />
                <InputError class="mt-2" :message="form.errors.telephone" />
            </div>

            <div>
                <InputLabel for="email" value="Email (optionnel)" />
                <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" autocomplete="username" />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Enregistrer</PrimaryButton>
                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">Enregistré.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
