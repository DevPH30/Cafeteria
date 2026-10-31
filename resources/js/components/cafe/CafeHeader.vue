<script setup lang="ts">
const props = defineProps<{
    page: string;
    cartCount: number;
    searchQuery: string;
    searchOpen: boolean;
}>();

const emit = defineEmits<{
    navigate: [page: string];
    'update:searchQuery': [query: string];
    search: [];
    toggleSearch: [];
}>();

const links = [
    { page: 'inicio', label: 'início', href: '/' },
    { page: 'produtos', label: 'produtos', href: '/produtos' },
    { page: 'pedidos', label: 'pedidos', href: '/pedidos' },
    { page: 'contatos', label: 'contatos', href: '/contatos' },
    { page: 'sobre', label: 'sobre nós', href: '/sobre-nos' },
];
</script>

<template>
    <header class="site-header">
        <a
            class="brand"
            href="/"
            aria-label="Café Aroma - início"
            @click.prevent="emit('navigate', 'inicio')"
        >
            <span class="brand-mark" aria-hidden="true">☕</span>
            <span>Café Aroma</span>
        </a>

        <nav class="main-nav" aria-label="Navegação principal">
            <a
                v-for="link in links"
                :key="link.page"
                :href="link.href"
                :aria-current="props.page === link.page ? 'page' : undefined"
                @click.prevent="emit('navigate', link.page)"
            >
                {{ link.label }}
            </a>
        </nav>

        <div class="header-actions">
            <button
                class="icon-button"
                type="button"
                aria-label="Buscar produtos"
                :aria-expanded="props.searchOpen"
                @click="emit('toggleSearch')"
            >
                <svg viewBox="0 0 40 40" aria-hidden="true">
                    <circle cx="17" cy="17" r="11" />
                    <path d="m25 25 9 9" />
                </svg>
            </button>
            <button
                class="icon-button"
                type="button"
                aria-label="Minha conta"
                @click="emit('navigate', 'inicio')"
            >
                <svg viewBox="0 0 40 40" aria-hidden="true">
                    <circle cx="20" cy="20" r="17" />
                    <circle cx="20" cy="15" r="6" />
                    <path d="M8 32c2-7 7-10 12-10s10 3 12 10" />
                </svg>
            </button>
            <button
                class="icon-button cart-trigger"
                type="button"
                aria-label="Abrir carrinho"
                @click="emit('navigate', 'checkout')"
            >
                <svg viewBox="0 0 42 40" aria-hidden="true">
                    <path d="M3 6h5l4 22h23l5-17H10M14 16h23M16 22h19" />
                    <circle cx="17" cy="34" r="2" />
                    <circle cx="32" cy="34" r="2" />
                    <path d="M27 5c-5-5 4-7 5-2 2-5 9-2 4 4l-4 4-5-6Z" />
                </svg>
                <span class="cart-count">{{ props.cartCount }}</span>
            </button>
        </div>

        <form
            class="search-panel"
            :hidden="!props.searchOpen"
            @submit.prevent="emit('search')"
        >
            <label for="site-search">O que você está procurando?</label>
            <input
                id="site-search"
                :value="props.searchQuery"
                type="search"
                placeholder="Buscar cafés, doces..."
                @input="
                    emit(
                        'update:searchQuery',
                        ($event.target as HTMLInputElement).value,
                    )
                "
            />
            <button class="button button-dark" type="submit">Buscar</button>
        </form>
    </header>
</template>
