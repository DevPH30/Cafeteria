<script setup lang="ts">
const emit = defineEmits<{
    navigate: [page: string];
}>();
</script>

<template>
    <footer class="site-footer">
        <a
            class="footer-brand"
            href="/"
            @click.prevent="emit('navigate', 'inicio')"
        >
            <span class="brand-mark">
                <img
                    src="/images/cafe/logo-cup.png"
                    alt=""
                    aria-hidden="true"
                />
            </span>
            <span>Café Aroma</span>
        </a>
        <span>Feito com carinho, servido com aroma.</span>
        <div class="footer-links">
            <a
                href="/acompanhar-pedido"
                @click.prevent="emit('navigate', 'acompanhar')"
            >
                acompanhar pedido
            </a>
            <a href="/admin" @click.prevent="emit('navigate', 'admin')">
                área administrativa
            </a>
        </div>
    </footer>
</template>
