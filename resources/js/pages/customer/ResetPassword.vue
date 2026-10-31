<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    email: string;
    token: string;
}>();

const form = useForm({
    email: props.email,
    token: props.token,
    password: '',
    password_confirmation: '',
});
const passwordMinimum = computed(() => (import.meta.env.PROD ? 12 : 8));

function submit() {
    form.post('/cliente/redefinir-senha', {
        onSuccess: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <Head title="Definir nova senha - Café Aroma" />

    <h1 class="customer-auth-title">Definir nova senha</h1>
    <p class="customer-auth-description">
        Crie uma nova senha para sua conta de cliente com pelo menos
        {{ passwordMinimum }} caracteres.
    </p>

    <form class="customer-register-form" @submit.prevent="submit">
        <div class="field">
            <label for="customer-reset-email">E-mail</label>
            <input
                id="customer-reset-email"
                v-model="form.email"
                type="email"
                autocomplete="email"
                maxlength="191"
                required
            />
            <span v-if="form.errors.email" class="customer-field-error">
                {{ form.errors.email }}
            </span>
        </div>

        <div class="field">
            <label for="customer-new-password">Nova senha</label>
            <input
                id="customer-new-password"
                v-model="form.password"
                type="password"
                autocomplete="new-password"
                :minlength="passwordMinimum"
                required
            />
            <span v-if="form.errors.password" class="customer-field-error">
                {{ form.errors.password }}
            </span>
        </div>

        <div class="field">
            <label for="customer-new-password-confirmation">
                Confirme a nova senha
            </label>
            <input
                id="customer-new-password-confirmation"
                v-model="form.password_confirmation"
                type="password"
                autocomplete="new-password"
                :minlength="passwordMinimum"
                required
            />
        </div>

        <button
            class="button button-dark customer-auth-submit"
            type="submit"
            :disabled="form.processing"
        >
            {{ form.processing ? 'Salvando...' : 'Redefinir senha' }}
        </button>
    </form>

    <p class="customer-auth-switch">
        <Link href="/">Voltar ao login do cliente</Link>
    </p>
</template>
