<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

const { title = '', description = '' } = defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <Head>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link
            rel="preconnect"
            href="https://fonts.gstatic.com"
            crossorigin="anonymous"
        />
        <link
            href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Poiret+One&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div class="cafe-site admin-auth-site">
        <header class="site-header">
            <a class="brand" href="/" aria-label="Café Aroma - início">
                <span class="brand-mark" aria-hidden="true">☕</span>
                <span>Café Aroma</span>
            </a>
            <nav class="main-nav" aria-label="Navegação principal">
                <a href="/">início</a>
                <a href="/produtos">produtos</a>
                <a href="/pedidos">pedidos</a>
                <a href="/contatos">contatos</a>
                <a href="/sobre-nos">sobre nós</a>
            </nav>
        </header>

        <main class="cafe-main admin-auth-main">
            <section class="page-wrap">
                <div class="admin-auth-card card">
                    <h1 class="page-title">{{ title }}</h1>
                    <p v-if="description" class="admin-auth-description">
                        {{ description }}
                    </p>
                    <slot />
                </div>
            </section>
        </main>

        <footer class="site-footer">
            <a class="footer-brand" href="/">☕ Café Aroma</a>
            <span>Feito com carinho, servido com aroma.</span>
            <div class="footer-links">
                <a href="/acompanhar-pedido">acompanhar pedido</a>
                <a href="/admin">área administrativa</a>
            </div>
        </footer>
    </div>
</template>
