<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

defineOptions({
    layout: {
        title: 'Cadastrar administrador',
        description: 'Crie uma conta administrativa para o sistema',
    },
});

const props = defineProps<{
    passwordRules: string;
}>();

const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/admin/administradores/cadastro', {
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <Head title="Cadastrar administrador" />

    <form class="flex flex-col gap-6" @submit.prevent="submit">
        <div class="grid gap-2">
            <Label for="admin-name">Nome</Label>
            <Input
                id="admin-name"
                v-model="form.name"
                autocomplete="name"
                maxlength="191"
                required
                v-focus
                placeholder="Nome completo"
            />
            <InputError :message="form.errors.name" />
        </div>

        <div class="grid gap-2">
            <Label for="admin-email">E-mail</Label>
            <Input
                id="admin-email"
                v-model="form.email"
                type="email"
                autocomplete="email"
                maxlength="191"
                required
                placeholder="email@exemplo.com"
            />
            <InputError :message="form.errors.email" />
        </div>

        <div class="grid gap-2">
            <Label for="admin-phone">Telefone</Label>
            <Input
                id="admin-phone"
                v-model="form.phone"
                type="tel"
                autocomplete="tel"
                maxlength="24"
                required
                placeholder="(61) 99999-9999"
            />
            <InputError :message="form.errors.phone" />
        </div>

        <div class="grid gap-2">
            <Label for="admin-password">Senha</Label>
            <PasswordInput
                id="admin-password"
                v-model="form.password"
                autocomplete="new-password"
                required
                :passwordrules="props.passwordRules"
                placeholder="Senha segura"
            />
            <InputError :message="form.errors.password" />
        </div>

        <div class="grid gap-2">
            <Label for="admin-password-confirmation">Confirmar senha</Label>
            <PasswordInput
                id="admin-password-confirmation"
                v-model="form.password_confirmation"
                autocomplete="new-password"
                required
                :passwordrules="props.passwordRules"
                placeholder="Digite a senha novamente"
            />
            <InputError :message="form.errors.password_confirmation" />
        </div>

        <Button
            type="submit"
            class="w-full"
            :disabled="form.processing"
            data-test="register-admin-button"
        >
            <Spinner v-if="form.processing" />
            Cadastrar administrador
        </Button>
    </form>
</template>
