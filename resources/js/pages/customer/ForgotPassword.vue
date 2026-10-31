<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    status?: string;
}>();

const form = useForm({ email: '' });

function submit() {
    form.post('/cliente/esqueci-senha');
}
</script>

<template>
    <Head title="Recuperar senha - Café Aroma" />

    <h1 class="customer-auth-title">Recuperar senha</h1>
    <p class="customer-auth-description">
        Informe o e-mail da sua conta de cliente. Se ele estiver cadastrado,
        enviaremos um link para redefinir sua senha.
    </p>

    <p v-if="status" class="customer-auth-status" role="status">
        {{ status }}
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

        <button
            class="button button-dark customer-auth-submit"
            type="submit"
            :disabled="form.processing"
        >
            {{ form.processing ? 'Enviando...' : 'Enviar link de recuperação' }}
        </button>
    </form>

    <p class="customer-auth-switch">
        <Link href="/">Voltar ao login do cliente</Link>
    </p>
</template>
