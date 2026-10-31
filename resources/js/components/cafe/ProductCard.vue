<script setup lang="ts">
export type CafeProduct = {
    id: string;
    name: string;
    category: 'coffee' | 'cupcakes' | 'news';
    group: string;
    price: number;
    description: string;
    image: string;
};

defineProps<{
    product: CafeProduct;
}>();

const emit = defineEmits<{
    add: [product: CafeProduct];
}>();

const formatPrice = (value: number) =>
    value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
</script>

<template>
    <article class="product-card card">
        <div class="product-image">
            <img :src="product.image" :alt="product.name" loading="lazy" />
            <span class="product-badge">{{ product.group }}</span>
        </div>
        <div class="product-info">
            <h3>{{ product.name }}</h3>
            <p>{{ product.description }}</p>
            <div class="product-bottom">
                <span class="price">{{ formatPrice(product.price) }}</span>
                <button
                    class="add-button"
                    type="button"
                    :aria-label="`Adicionar ${product.name} ao carrinho`"
                    @click="emit('add', product)"
                >
                    +
                </button>
            </div>
        </div>
    </article>
</template>
