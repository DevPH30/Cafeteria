<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { vMaska } from 'maska/vue';
import { brazilianStateAbbreviations } from '@/data/brazilian-state-abbreviations';
import PasswordInput from '@/components/PasswordInput.vue';

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
const passwordMinimum = 8;

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
        Cadastre seus dados para acompanhar seus pedidos.
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
                v-maska="{ mask: ['(##) ####-####', '(##) #####-####'] }"
                v-model="form.phone"
                type="tel"
                autocomplete="tel"
                inputmode="numeric"
                maxlength="15"
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
                    v-maska="'#####-###'"
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
                <select
                    id="customer-state"
                    v-model="form.state"
                    autocomplete="address-level1"
                    required
                >
                    <option value="" disabled>Selecione o estado</option>
                    <option
                        v-for="state in brazilianStateAbbreviations"
                        :key="state"
                        :value="state"
                    >
                        {{ state }}
                    </option>
                </select>
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
            <span class="muted small">
                A senha deve ter pelo menos {{ passwordMinimum }} caracteres.
            </span>
            <PasswordInput
                id="customer-password"
                v-model="form.password"
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
            <PasswordInput
                id="customer-password-confirmation"
                v-model="form.password_confirmation"
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
