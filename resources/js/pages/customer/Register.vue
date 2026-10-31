<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    phone: '',
    zip_code: '',
    neighborhood: '',
    city: '',
    state: '',
    address: '',
});
const passwordMinimum = computed(() => (import.meta.env.PROD ? 12 : 8));

function submit() {
    form.post('/cadastro', {
        onSuccess: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <Head title="Criar conta de cliente - Café Aroma" />

    <h1 class="customer-auth-title">Criar conta de cliente</h1>
    <p class="customer-auth-description">
        Cadastre seus dados para acompanhar seus pedidos. A senha deve ter pelo
        menos {{ passwordMinimum }} caracteres.
    </p>

    <form class="customer-register-form" @submit.prevent="submit">
        <div class="field">
            <label for="customer-name">Nome completo</label>
            <input
                id="customer-name"
                v-model="form.name"
                autocomplete="name"
                maxlength="191"
                required
            />
            <span v-if="form.errors.name" class="customer-field-error">
                {{ form.errors.name }}
            </span>
        </div>

        <div class="field">
            <label for="customer-email">E-mail</label>
            <input
                id="customer-email"
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
            <label for="customer-phone">Telefone com DDD</label>
            <input
                id="customer-phone"
                v-model="form.phone"
                type="tel"
                autocomplete="tel"
                maxlength="24"
                placeholder="(11) 99999-9999"
                required
            />
            <span v-if="form.errors.phone" class="customer-field-error">
                {{ form.errors.phone }}
            </span>
        </div>

        <div class="customer-address-fields">
            <div class="field">
                <label for="customer-zip-code">CEP</label>
                <input
                    id="customer-zip-code"
                    v-model="form.zip_code"
                    autocomplete="postal-code"
                    inputmode="numeric"
                    maxlength="9"
                    pattern="[0-9]{5}-?[0-9]{3}"
                    placeholder="00000-000"
                    required
                />
                <span v-if="form.errors.zip_code" class="customer-field-error">
                    {{ form.errors.zip_code }}
                </span>
            </div>

            <div class="field">
                <label for="customer-neighborhood">Bairro</label>
                <input
                    id="customer-neighborhood"
                    v-model="form.neighborhood"
                    autocomplete="address-level3"
                    maxlength="120"
                    required
                />
                <span
                    v-if="form.errors.neighborhood"
                    class="customer-field-error"
                >
                    {{ form.errors.neighborhood }}
                </span>
            </div>

            <div class="field">
                <label for="customer-city">Cidade</label>
                <input
                    id="customer-city"
                    v-model="form.city"
                    autocomplete="address-level2"
                    maxlength="120"
                    required
                />
                <span v-if="form.errors.city" class="customer-field-error">
                    {{ form.errors.city }}
                </span>
            </div>

            <div class="field">
                <label for="customer-state">Estado (UF)</label>
                <input
                    id="customer-state"
                    v-model="form.state"
                    autocomplete="address-level1"
                    maxlength="2"
                    pattern="[A-Za-z]{2}"
                    placeholder="SP"
                    required
                    @input="form.state = form.state.toUpperCase()"
                />
                <span v-if="form.errors.state" class="customer-field-error">
                    {{ form.errors.state }}
                </span>
            </div>

            <div class="field customer-address-street">
                <label for="customer-address">Endereço (rua e número)</label>
                <input
                    id="customer-address"
                    v-model="form.address"
                    autocomplete="street-address"
                    maxlength="191"
                    required
                />
                <span v-if="form.errors.address" class="customer-field-error">
                    {{ form.errors.address }}
                </span>
            </div>
        </div>

        <div class="field">
            <label for="customer-password">Senha</label>
            <input
                id="customer-password"
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
            <label for="customer-password-confirmation">Confirme a senha</label>
            <input
                id="customer-password-confirmation"
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
            {{ form.processing ? 'Criando conta...' : 'Criar conta' }}
        </button>
    </form>

    <p class="customer-auth-switch">
        Já tem cadastro?
        <Link href="/">Entre pela página inicial</Link>
    </p>
</template>
