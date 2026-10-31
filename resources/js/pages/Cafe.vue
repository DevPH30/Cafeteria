<script setup lang="ts">
import { Head, Link, router as inertiaRouter, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Pencil, Trash2 } from '@lucide/vue';
import { useRoute, useRouter } from 'vue-router';
import CalendarPicker from '@/components/cafe/CalendarPicker.vue';
import CafeFooter from '@/components/cafe/CafeFooter.vue';
import CafeHeader from '@/components/cafe/CafeHeader.vue';
import ProductCard, {
    type CafeProduct,
} from '@/components/cafe/ProductCard.vue';
import { logout } from '@/routes';

const props = defineProps<{
    auth: {
        user: { id: number; name: string; email: string } | null;
        customer: { id: number; name: string; email: string } | null;
    };
    customerStatus?: string | null;
    adminStatus?: string | null;
    catalogProducts?: CafeProduct[];
}>();

type Category = CafeProduct['category'];
type CartItem = { id: string; quantity: number };
type OrderItem = {
    id: string;
    name: string;
    price: number;
    quantity: number;
};
type CafeOrder = {
    number: string;
    name: string;
    email?: string;
    status: string;
    total: number;
    fulfillment: 'pickup' | 'delivery';
    date?: string;
    time?: string;
    message?: string;
    request?: boolean;
    paymentMethod?: string;
    items: OrderItem[];
};
type InventoryItem = {
    name: string;
    stock: number;
    minimum: number;
    productId?: string;
};
type PickupRequest = {
    name: string;
    email: string;
    message: string;
    time: string;
    date: string;
};
type StoredCafeState = {
    cart: CartItem[];
    inventory: InventoryItem[];
    orders: CafeOrder[];
    customProducts: CafeProduct[];
    pendingOrder: PickupRequest | null;
};

const storageKey = 'cafe-aroma-state-v1';
const initialProducts: CafeProduct[] = [
    {
        id: 'coffee',
        name: 'Coffee',
        category: 'coffee',
        group: 'Cafés',
        price: 30,
        description: 'Espresso encorpado, extraído na hora.',
        image: '/images/cafe/hero-coffee.png',
    },
    {
        id: 'latte',
        name: 'Latte Macchiato',
        category: 'coffee',
        group: 'Cafés',
        price: 50,
        description: 'Leite vaporizado, espresso e uma camada cremosa.',
        image: '/images/cafe/latte.png',
    },
    {
        id: 'mocha',
        name: 'Mocha',
        category: 'coffee',
        group: 'Cafés',
        price: 30,
        description: 'Café, chocolate e leite cremoso.',
        image: '/images/cafe/iced-coffee.png',
    },
    {
        id: 'coffee-milk',
        name: 'Coffee with milk',
        category: 'coffee',
        group: 'Cafés',
        price: 35,
        description: 'Café especial com leite vaporizado.',
        image: '/images/cafe/espresso.png',
    },
    {
        id: 'pistachio',
        name: 'Buttery Pistachio',
        category: 'cupcakes',
        group: 'Cupcakes',
        price: 60,
        description: 'Cupcake macio com pistache e cobertura delicada.',
        image: '/images/cafe/pistachio-cupcake.png',
    },
    {
        id: 'belgian',
        name: 'Belgian Chocolate',
        category: 'cupcakes',
        group: 'Cupcakes',
        price: 50,
        description: 'Chocolate belga, massa leve e cobertura cremosa.',
        image: '/images/cafe/chocolate-cupcake.png',
    },
    {
        id: 'chocolate',
        name: 'Belgian Chocolate 60',
        category: 'cupcakes',
        group: 'Cupcakes',
        price: 60,
        description: 'Chocolate intenso para os apaixonados por cacau.',
        image: '/images/cafe/chocolate-cupcake.png',
    },
    {
        id: 'lemon',
        name: 'Sicilian Lemon and Blueberry',
        category: 'cupcakes',
        group: 'Cupcakes',
        price: 70,
        description: 'Limão siciliano, mirtilo e cobertura artesanal.',
        image: '/images/cafe/blueberry-cupcake.png',
    },
    {
        id: 'iced-latte',
        name: 'Iced Latte',
        category: 'news',
        group: 'Novidades',
        price: 30,
        description: 'Espresso gelado com leite e muito sabor.',
        image: '/images/cafe/iced-coffee.png',
    },
    {
        id: 'cappuccino',
        name: 'Cappuccino',
        category: 'news',
        group: 'Novidades',
        price: 25,
        description: 'Cappuccino aveludado com latte art.',
        image: '/images/cafe/cappuccino.png',
    },
    {
        id: 'caramel',
        name: 'Mocha with Caramel',
        category: 'news',
        group: 'Novidades',
        price: 30,
        description: 'Mocha com caramelo e chantilly feito na casa.',
        image: '/images/cafe/latte.png',
    },
    {
        id: 'iced-coffee',
        name: 'Iced Coffee',
        category: 'news',
        group: 'Novidades',
        price: 45,
        description: 'Café especial servido gelado.',
        image: '/images/cafe/iced-coffee.png',
    },
];

const initialInventory: InventoryItem[] = [
    { name: 'Buttery Pistachio 60', stock: 120, minimum: 20 },
    { name: 'Belgian Chocolate 60', stock: 60, minimum: 20 },
    { name: 'Espresso 50', stock: 122, minimum: 20 },
];

const demoOrders: CafeOrder[] = [
    {
        number: '23020011',
        name: 'Nome Alieção',
        status: 'Em Produção',
        total: 148,
        fulfillment: 'delivery',
        items: [],
    },
    {
        number: '23022632',
        name: 'Maria Caintos',
        status: 'Pronto para Entrega',
        total: 98,
        fulfillment: 'delivery',
        items: [],
    },
    {
        number: '23028763',
        name: 'Livra Aroma',
        status: 'Pronto para Entrega',
        total: 75,
        fulfillment: 'delivery',
        items: [],
    },
];

const allowedPages = [
    'inicio',
    'produtos',
    'contatos',
    'pedidos',
    'acompanhar',
    'checkout',
    'sobre',
    'admin',
] as const;
type CafePage = (typeof allowedPages)[number];

const pageRouteNames: Record<CafePage, string> = {
    inicio: 'home',
    produtos: 'products',
    contatos: 'contact',
    pedidos: 'orders',
    acompanhar: 'order-tracking',
    checkout: 'checkout',
    sobre: 'about',
    admin: 'cafe-admin',
};
const route = useRoute();
const router = useRouter();
const page = computed(
    () =>
        allowedPages.find((candidate) => candidate === route.meta.page) ??
        'inicio',
);
const products = ref<CafeProduct[]>([
    ...initialProducts,
    ...(props.catalogProducts ?? []),
]);
const customProducts = ref<CafeProduct[]>(props.catalogProducts ?? []);
const cart = ref<CartItem[]>([]);
const inventory = ref<InventoryItem[]>([...initialInventory]);
const orders = ref<CafeOrder[]>([]);
const pendingOrder = ref<PickupRequest | null>(null);
const activeCategory = ref<'all' | Category>('all');
const searchQuery = ref('');
const searchOpen = ref(false);
const selectedDate = ref<string | null>(null);
const fulfillment = ref<'pickup' | 'delivery'>('delivery');
const paymentMethod = ref('pickup');
const coupon = ref('');
const couponApplied = ref(false);
const toastMessage = ref('');
const loginForm = useForm({
    email: '',
    password: '',
});
const editingProduct = ref<CafeProduct | null>(null);
const productImageInput = ref<HTMLInputElement | null>(null);
const productForm = useForm({
    name: '',
    price: null as number | null,
    category: 'coffee' as Category,
    image: null as File | null,
});
const inventoryName = ref('');
const inventoryStock = ref<number | null>(null);
let toastTimer: ReturnType<typeof setTimeout> | undefined;

const cartCount = computed(() =>
    cart.value.reduce((total, item) => total + item.quantity, 0),
);
const filteredProducts = computed(() => {
    const query = searchQuery.value.trim().toLocaleLowerCase('pt-BR');

    return products.value.filter((product) => {
        const matchesCategory =
            activeCategory.value === 'all' ||
            product.category === activeCategory.value;
        const matchesSearch =
            !query ||
            `${product.name} ${product.description} ${product.group}`
                .toLocaleLowerCase('pt-BR')
                .includes(query);

        return matchesCategory && matchesSearch;
    });
});
const cartLines = computed(() =>
    cart.value.flatMap((item) => {
        const product = products.value.find((entry) => entry.id === item.id);

        return product ? [{ product, quantity: item.quantity }] : [];
    }),
);
const subtotal = computed(() =>
    cartLines.value.reduce(
        (total, item) => total + item.product.price * item.quantity,
        0,
    ),
);
const discount = computed(() =>
    couponApplied.value ? subtotal.value * 0.1 : 0,
);
const deliveryFee = computed(() =>
    cartCount.value > 0 && fulfillment.value === 'delivery' ? 10 : 0,
);
const total = computed(() =>
    Math.max(0, subtotal.value + deliveryFee.value - discount.value),
);
const latestOrder = computed(
    () => orders.value[orders.value.length - 1] ?? null,
);
const trackingSteps = computed(() =>
    latestOrder.value?.fulfillment === 'pickup'
        ? [
              'Pedido Recebido',
              'Em Produção',
              'Está pronto o pedido',
              'Pedido retirado',
          ]
        : ['Pedido Recebido', 'Em Produção', 'Saiu para Entrega', 'Entregue'],
);
const trackingStatus = computed(() => {
    const status = latestOrder.value?.status ?? 'Em Produção';

    if (latestOrder.value?.fulfillment === 'pickup') {
        if (status === 'Pronto para Entrega') return 'Está pronto o pedido';
        if (status === 'Entregue') return 'Pedido retirado';
    }

    return status === 'Em Preparo' ? 'Em Produção' : status;
});
const trackingStepIndex = computed(() =>
    Math.max(0, trackingSteps.value.indexOf(trackingStatus.value)),
);
const adminOrders = computed(() =>
    orders.value.length ? orders.value : demoOrders,
);

function isCafeProduct(value: unknown): value is CafeProduct {
    if (!value || typeof value !== 'object') return false;

    const product = value as Record<string, unknown>;

    return (
        typeof product.id === 'string' &&
        typeof product.name === 'string' &&
        (product.category === 'coffee' ||
            product.category === 'cupcakes' ||
            product.category === 'news') &&
        typeof product.group === 'string' &&
        typeof product.price === 'number' &&
        typeof product.description === 'string' &&
        typeof product.image === 'string'
    );
}

function isCartItem(value: unknown): value is CartItem {
    if (!value || typeof value !== 'object') return false;

    const item = value as Record<string, unknown>;

    return (
        typeof item.id === 'string' &&
        typeof item.quantity === 'number' &&
        Number.isInteger(item.quantity) &&
        item.quantity > 0
    );
}

function isInventoryItem(value: unknown): value is InventoryItem {
    if (!value || typeof value !== 'object') return false;

    const item = value as Record<string, unknown>;

    return (
        typeof item.name === 'string' &&
        typeof item.stock === 'number' &&
        typeof item.minimum === 'number'
    );
}

function readSavedState() {
    try {
        const raw = localStorage.getItem(storageKey);
        if (!raw) return;

        const saved: unknown = JSON.parse(raw);
        if (!saved || typeof saved !== 'object') return;

        const state = saved as Record<string, unknown>;

        if (Array.isArray(state.cart)) {
            cart.value = state.cart.filter(isCartItem);
        }
        if (Array.isArray(state.inventory)) {
            inventory.value = state.inventory.filter(isInventoryItem);
        }
        if (Array.isArray(state.orders)) {
            orders.value = state.orders.filter(
                (order): order is CafeOrder =>
                    Boolean(order) &&
                    typeof order === 'object' &&
                    typeof (order as CafeOrder).number === 'string' &&
                    typeof (order as CafeOrder).status === 'string' &&
                    Array.isArray((order as CafeOrder).items),
            );
        }
        if (
            state.pendingOrder &&
            typeof state.pendingOrder === 'object' &&
            typeof (state.pendingOrder as PickupRequest).name === 'string' &&
            typeof (state.pendingOrder as PickupRequest).date === 'string'
        ) {
            pendingOrder.value = state.pendingOrder as PickupRequest;
            fulfillment.value = 'pickup';
        }
    } catch (error) {
        console.warn(
            'Não foi possível ler os dados salvos do Café Aroma.',
            error,
        );
        showToast(
            'Não foi possível recuperar os dados salvos neste navegador.',
        );
    }
}

function persistState() {
    const state: StoredCafeState = {
        cart: cart.value,
        inventory: inventory.value,
        orders: orders.value,
        customProducts: customProducts.value,
        pendingOrder: pendingOrder.value,
    };

    try {
        localStorage.setItem(storageKey, JSON.stringify(state));
    } catch (error) {
        console.error('Não foi possível salvar os dados do Café Aroma.', error);
        showToast('Não foi possível salvar as alterações neste navegador.');
    }
}

function navigate(target: string, category?: 'all' | Category) {
    const destination =
        allowedPages.find((candidate) => candidate === target) ?? 'inicio';

    if (category) activeCategory.value = category;
    searchOpen.value = false;
    void router.push({ name: pageRouteNames[destination] });
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function showToast(message: string) {
    toastMessage.value = message;
    if (toastTimer) window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(() => {
        toastMessage.value = '';
    }, 2800);
}

function formatPrice(value: number) {
    return value.toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    });
}

function addToCart(product: CafeProduct) {
    const existing = cart.value.find((item) => item.id === product.id);
    if (existing) existing.quantity += 1;
    else cart.value.push({ id: product.id, quantity: 1 });

    persistState();
    showToast(`${product.name} adicionado ao carrinho.`);
}

function changeQuantity(id: string, amount: number) {
    const item = cart.value.find((entry) => entry.id === id);
    if (!item) return;

    item.quantity += amount;
    cart.value = cart.value.filter((entry) => entry.quantity > 0);
    persistState();
}

function removeFromCart(id: string) {
    cart.value = cart.value.filter((item) => item.id !== id);
    persistState();
    showToast('Item removido do carrinho.');
}

function submitSearch() {
    activeCategory.value = 'all';
    navigate('produtos');
}

function submitLogin() {
    loginForm.post('/cliente/login', {
        onSuccess: () => loginForm.reset('password'),
    });
}

function logoutCustomer() {
    inertiaRouter.post('/cliente/logout');
}

function submitPickupRequest(event: SubmitEvent) {
    const form = event.currentTarget;
    if (!(form instanceof HTMLFormElement)) return;

    if (!selectedDate.value) {
        showToast('Escolha uma data no calendário antes de enviar o pedido.');
        return;
    }

    const data = new FormData(form);
    pendingOrder.value = {
        name: String(data.get('name')),
        email: String(data.get('email')),
        message: String(data.get('message')),
        time: String(data.get('time')),
        date: selectedDate.value,
    };
    fulfillment.value = 'pickup';
    persistState();
    navigate('checkout');
}

function applyCoupon() {
    if (coupon.value.trim().toLocaleUpperCase('pt-BR') !== 'AROMA10') {
        showToast('Cupom não encontrado. Tente AROMA10.');
        return;
    }

    couponApplied.value = true;
    try {
        sessionStorage.setItem('cafe-aroma-coupon', 'AROMA10');
    } catch (error) {
        console.warn('Não foi possível salvar o cupom desta sessão.', error);
    }
    showToast('Cupom AROMA10 aplicado: 10% de desconto.');
}

function copyPixKey() {
    const pixKey = 'contato@cafearoma.com.br';

    if (!navigator.clipboard || !window.isSecureContext) {
        showToast(`Chave PIX demonstrativa: ${pixKey}`);
        return;
    }

    void navigator.clipboard
        .writeText(pixKey)
        .then(() => showToast('Chave PIX demonstrativa copiada.'))
        .catch((error: unknown) => {
            console.error('Falha ao copiar a chave PIX.', error);
            showToast(`Chave PIX demonstrativa: ${pixKey}`);
        });
}

function confirmOrder() {
    if (!cartCount.value && !pendingOrder.value) {
        showToast('Adicione itens ao carrinho ou preencha os dados do pedido.');
        return;
    }

    const order: CafeOrder = {
        number: String(Date.now()).slice(-6),
        name: pendingOrder.value?.name || loginForm.email || 'Cliente',
        email: pendingOrder.value?.email,
        message: pendingOrder.value?.message,
        date: pendingOrder.value?.date,
        time: pendingOrder.value?.time,
        request: Boolean(pendingOrder.value),
        fulfillment: fulfillment.value,
        status: 'Em Produção',
        total: total.value,
        items: cartLines.value.map(({ product, quantity }) => ({
            id: product.id,
            name: product.name,
            price: product.price,
            quantity,
        })),
        paymentMethod: paymentMethod.value,
    };

    orders.value.push(order);
    cart.value = [];
    pendingOrder.value = null;
    couponApplied.value = false;
    coupon.value = '';
    try {
        sessionStorage.removeItem('cafe-aroma-coupon');
    } catch (error) {
        console.warn('Não foi possível limpar o cupom da sessão.', error);
    }
    persistState();
    navigate('acompanhar');
    showToast(`Pedido #${order.number} confirmado. Acompanhe o andamento.`);
}

function addInventoryItem() {
    if (!inventoryName.value.trim() || inventoryStock.value === null) return;

    inventory.value.push({
        name: inventoryName.value.trim(),
        stock: inventoryStock.value,
        minimum: 20,
    });
    inventoryName.value = '';
    inventoryStock.value = null;
    persistState();
    showToast('Produto adicionado ao estoque.');
}

function changeStock(index: number, amount: number) {
    const item = inventory.value[index];
    if (!item) return;

    item.stock = Math.max(0, item.stock + amount);
    persistState();
}

function removeInventoryItem(index: number) {
    const [removed] = inventory.value.splice(index, 1);
    if (!removed) return;

    persistState();
    showToast(`${removed.name} removido do estoque.`);
}

function addProductToCatalog() {
    productForm.clearErrors();
    productForm
        .transform((data) => ({
            ...data,
            name: data.name.trim(),
            _method: editingProduct.value ? 'put' : undefined,
        }))
        .post(
            editingProduct.value
                ? `/admin/produtos/${editingProduct.value.id}`
                : '/admin/produtos',
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => {
                    const productName = productForm.name;
                    customProducts.value = [...(props.catalogProducts ?? [])];
                    products.value = [
                        ...initialProducts,
                        ...customProducts.value,
                    ];
                    if (
                        !editingProduct.value &&
                        customProducts.value.some(
                            (product) => product.name === productName,
                        )
                    ) {
                        const product = customProducts.value.find(
                            (entry) => entry.name === productName,
                        );
                        if (product) {
                            inventory.value.push({
                                name: `${product.name} ${product.price}`,
                                stock: 0,
                                minimum: 20,
                                productId: product.id,
                            });
                        }
                    }
                    editingProduct.value = null;
                    productForm.reset();
                    if (productImageInput.value) {
                        productImageInput.value.value = '';
                    }
                    persistState();
                },
            },
        );
}

function editProduct(product: CafeProduct) {
    editingProduct.value = product;
    productForm.name = product.name;
    productForm.price = product.price;
    productForm.category = product.category;
    productForm.image = null;
    productForm.clearErrors();
}

function cancelProductEdit() {
    editingProduct.value = null;
    productForm.reset();
    productForm.clearErrors();
    if (productImageInput.value) productImageInput.value.value = '';
}

function removeCustomProduct(product: CafeProduct) {
    inertiaRouter.delete(`/admin/produtos/${product.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            customProducts.value = [...(props.catalogProducts ?? [])];
            products.value = [...initialProducts, ...customProducts.value];
            inventory.value = inventory.value.filter(
                (item) => item.productId !== product.id,
            );
            cart.value = cart.value.filter((item) => item.id !== product.id);
            persistState();
        },
    });
}

function updateOrderStatus(index: number, status: string) {
    if (!orders.value.length)
        orders.value = demoOrders.map((order) => ({ ...order }));
    const order = orders.value[index];
    if (!order) return;

    order.status = status;
    persistState();
    showToast(`Status do pedido #${order.number} atualizado.`);
}

function handlePaymentMethodChange() {
    if (paymentMethod.value === 'credit' || paymentMethod.value === 'debit') {
        showToast(
            'Pagamento com cartão fictício: nenhuma cobrança será realizada.',
        );
    }
}

onMounted(() => {
    readSavedState();
    if (props.customerStatus) showToast(props.customerStatus);
    if (props.adminStatus) showToast(props.adminStatus);

    try {
        couponApplied.value =
            sessionStorage.getItem('cafe-aroma-coupon') === 'AROMA10';
    } catch (error) {
        console.warn('Não foi possível recuperar o cupom desta sessão.', error);
    }
});

onBeforeUnmount(() => {
    if (toastTimer) window.clearTimeout(toastTimer);
});
</script>

<template>
    <Head title="Café Aroma — um café, muitos momentos">
        <meta
            name="description"
            content="Cafés especiais e doces artesanais preparados com carinho no Café Aroma."
        />
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link
            rel="preconnect"
            href="https://fonts.gstatic.com"
            crossorigin="anonymous"
        />
        <link
            href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Poiret+One&family=Quicksand:wght@400;500;600;700&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div class="cafe-site">
        <CafeHeader
            :page="page"
            :cart-count="cartCount"
            :search-query="searchQuery"
            :search-open="searchOpen"
            @navigate="navigate"
            @update:search-query="searchQuery = $event"
            @search="submitSearch"
            @toggle-search="searchOpen = !searchOpen"
        />

        <main class="cafe-main" tabindex="-1">
            <template v-if="page === 'inicio'">
                <div class="home-wrap">
                    <section class="home-hero">
                        <div class="hero-copy">
                            <span class="eyebrow"
                                >pausa, aroma e aconchego</span
                            >
                            <h1>Seu momento<br />começa aqui.</h1>
                            <p>
                                Cafés especiais e doces artesanais preparados
                                com carinho, para deixar o seu dia mais gostoso.
                            </p>
                            <div class="hero-actions">
                                <a
                                    class="button"
                                    href="/produtos"
                                    @click.prevent="navigate('produtos')"
                                >
                                    Conheça o cardápio
                                </a>
                                <a
                                    class="button"
                                    href="/sobre-nos"
                                    @click.prevent="navigate('sobre')"
                                >
                                    Nossa história
                                </a>
                            </div>
                        </div>
                        <section
                            v-if="props.auth.customer"
                            class="login-card card"
                        >
                            <h2>Olá, {{ props.auth.customer.name }}!</h2>
                            <p class="muted small login-intro">
                                Conectado como {{ props.auth.customer.email }}
                            </p>
                            <button
                                class="button button-dark customer-auth-submit"
                                type="button"
                                @click="navigate('pedidos')"
                            >
                                Fazer um pedido
                            </button>
                            <button
                                class="button customer-auth-submit"
                                type="button"
                                @click="logoutCustomer"
                            >
                                Sair da conta
                            </button>
                        </section>
                        <form
                            v-else
                            class="login-card card"
                            @submit.prevent="submitLogin"
                        >
                            <h2>Bem-vindo(a)</h2>
                            <p class="muted small login-intro">
                                Entre para acompanhar seus pedidos
                            </p>
                            <div class="form-grid">
                                <div class="field">
                                    <label for="login-user">E-mail</label>
                                    <input
                                        id="login-user"
                                        v-model="loginForm.email"
                                        type="email"
                                        autocomplete="email"
                                        placeholder="seu@email.com"
                                        required
                                    />
                                    <span
                                        v-if="loginForm.errors.email"
                                        class="customer-field-error"
                                    >
                                        {{ loginForm.errors.email }}
                                    </span>
                                </div>
                                <div class="field">
                                    <label for="login-password">Senha</label>
                                    <input
                                        id="login-password"
                                        v-model="loginForm.password"
                                        type="password"
                                        autocomplete="current-password"
                                        required
                                    />
                                    <span
                                        v-if="loginForm.errors.password"
                                        class="customer-field-error"
                                    >
                                        {{ loginForm.errors.password }}
                                    </span>
                                </div>
                                <button
                                    class="button button-dark"
                                    type="submit"
                                    :disabled="loginForm.processing"
                                >
                                    {{
                                        loginForm.processing
                                            ? 'Entrando...'
                                            : 'Entrar'
                                    }}
                                </button>
                                <div class="login-links">
                                    <Link href="/cadastro">Criar conta</Link>
                                    <Link href="/cliente/esqueci-senha">
                                        Esqueci a senha
                                    </Link>
                                </div>
                            </div>
                        </form>
                    </section>
                    <section
                        class="home-categories"
                        aria-label="Categorias do cardápio"
                    >
                        <a
                            class="category-card card"
                            href="/produtos"
                            @click.prevent="navigate('produtos', 'coffee')"
                        >
                            <div>
                                <strong>Cafés especiais</strong>
                                <span>Do espresso ao cappuccino</span>
                            </div>
                            <span class="category-icon">☕</span>
                        </a>
                        <a
                            class="category-card card"
                            href="/produtos"
                            @click.prevent="navigate('produtos', 'cupcakes')"
                        >
                            <div>
                                <strong>Cupcakes</strong>
                                <span>Doces feitos à mão</span>
                            </div>
                            <span class="category-icon">🧁</span>
                        </a>
                        <a
                            class="category-card card"
                            href="/pedidos"
                            @click.prevent="navigate('pedidos')"
                        >
                            <div>
                                <strong>Faça seu pedido</strong>
                                <span>Escolha o melhor horário</span>
                            </div>
                            <span class="category-icon">♡</span>
                        </a>
                    </section>
                </div>
            </template>

            <section v-else-if="page === 'produtos'" class="page-wrap">
                <h1 class="page-title">Produtos</h1>
                <div class="catalog-tools">
                    <div
                        class="category-tabs"
                        role="group"
                        aria-label="Filtrar por categoria"
                    >
                        <button
                            v-for="category in [
                                { id: 'all', label: 'Todos' },
                                { id: 'coffee', label: 'Cafés' },
                                { id: 'cupcakes', label: 'Cupcakes' },
                                { id: 'news', label: 'Novidades' },
                            ] as const"
                            :key="category.id"
                            class="tab-button"
                            type="button"
                            :aria-pressed="activeCategory === category.id"
                            @click="activeCategory = category.id"
                        >
                            {{ category.label }}
                        </button>
                    </div>
                    <span class="muted small">
                        {{ filteredProducts.length }} delícias no cardápio
                    </span>
                </div>
                <div v-if="filteredProducts.length" class="product-grid">
                    <ProductCard
                        v-for="product in filteredProducts"
                        :key="product.id"
                        :product="product"
                        @add="addToCart"
                    />
                </div>
                <p v-else class="notice">
                    Nenhum produto encontrado para “{{ searchQuery }}”.
                </p>
            </section>

            <section v-else-if="page === 'contatos'" class="page-wrap">
                <h1 class="page-title">Contatos</h1>
                <div class="contact-layout">
                    <div class="contact-list">
                        <a
                            class="contact-row"
                            href="https://instagram.com/"
                            target="_blank"
                            rel="noreferrer"
                        >
                            <span class="contact-icon">◎</span>
                            <span>
                                <strong>Instagram</strong>
                                <span>@cafearoma</span>
                            </span>
                        </a>
                        <a class="contact-row" href="tel:+553333333333">
                            <span class="contact-icon">♧</span>
                            <span>
                                <strong>Telefone</strong>
                                <span>+55 (33) 3333-3333</span>
                            </span>
                        </a>
                        <div class="contact-row">
                            <span class="contact-icon">⌖</span>
                            <span>
                                <strong>Morada</strong>
                                <span>Rua do Aroma, 123 — Centro</span>
                            </span>
                        </div>
                        <a
                            class="contact-row"
                            href="mailto:contato@cafearoma.com.br"
                        >
                            <span class="contact-icon">✉</span>
                            <span>
                                <strong>E-mail</strong>
                                <span>contato@cafearoma.com.br</span>
                            </span>
                        </a>
                        <button
                            class="button contact-order-button"
                            type="button"
                            @click="navigate('pedidos')"
                        >
                            Fazer um pedido
                        </button>
                    </div>
                    <a
                        class="map-card card"
                        href="https://maps.google.com/?q=Rua+do+Aroma+123"
                        target="_blank"
                        rel="noreferrer"
                        aria-label="Abrir localização do Café Aroma no mapa"
                    >
                        <div class="map-grid"></div>
                        <div class="map-road"></div>
                        <div class="map-road road-two"></div>
                        <div class="map-pin"><span>☕</span></div>
                        <span class="map-caption">
                            Café Aroma · encontre-nos aqui ↗
                        </span>
                    </a>
                </div>
            </section>

            <section v-else-if="page === 'pedidos'" class="page-wrap">
                <h1 class="page-title">Pedidos</h1>
                <div class="order-layout">
                    <form
                        class="order-form card form-grid"
                        @submit.prevent="submitPickupRequest"
                    >
                        <span class="eyebrow">retirada na cafeteria</span>
                        <h2 class="section-title order-section-title">
                            Reserve seu momento
                        </h2>
                        <div class="field">
                            <label for="order-name">Nome</label>
                            <input
                                id="order-name"
                                name="name"
                                placeholder="Seu nome"
                                required
                            />
                        </div>
                        <div class="field">
                            <label for="order-email">E-mail</label>
                            <input
                                id="order-email"
                                name="email"
                                type="email"
                                placeholder="seu@email.com"
                                required
                            />
                        </div>
                        <div class="field">
                            <label for="order-message">Mensagem</label>
                            <textarea
                                id="order-message"
                                name="message"
                                placeholder="Conte para nós o que você gostaria de pedir..."
                                required
                            ></textarea>
                        </div>
                        <div class="field">
                            <label for="order-time"
                                >Horário para retirada</label
                            >
                            <select id="order-time" name="time" required>
                                <option value="">Selecione um horário</option>
                                <option>09:00</option>
                                <option>10:30</option>
                                <option>12:00</option>
                                <option>14:00</option>
                                <option>16:00</option>
                            </select>
                        </div>
                        <button class="button button-dark" type="submit">
                            Ir para pagamento
                        </button>
                    </form>
                    <div>
                        <div class="order-gallery">
                            <img
                                src="/images/cafe/iced-coffee.png"
                                alt="Latte gelado"
                                loading="lazy"
                            />
                            <img
                                src="/images/cafe/hero-coffee.png"
                                alt="Espresso duplo"
                                loading="lazy"
                            />
                            <img
                                src="/images/cafe/pistachio-cupcake.png"
                                alt="Cupcake artesanal"
                                loading="lazy"
                            />
                            <img
                                src="/images/cafe/chocolate-cupcake.png"
                                alt="Cupcake de chocolate"
                                loading="lazy"
                            />
                        </div>
                        <CalendarPicker v-model="selectedDate" />
                    </div>
                </div>
            </section>

            <section v-else-if="page === 'acompanhar'" class="page-wrap">
                <h1 class="page-title">
                    Acompanhar pedido <span aria-hidden="true">♧</span>
                </h1>
                <div class="tracking-panel card">
                    <div class="tracking-top">
                        <div>
                            <span class="eyebrow">Café Aroma</span>
                            <h2>
                                Status do pedido #{{
                                    latestOrder?.number ?? '12345'
                                }}
                            </h2>
                        </div>
                        <span class="status-pill">{{ trackingStatus }}</span>
                    </div>
                    <div class="tracking-layout">
                        <div class="timeline">
                            <div
                                v-for="(step, index) in trackingSteps"
                                :key="step"
                                class="timeline-step"
                                :class="{ done: index <= trackingStepIndex }"
                            >
                                <span class="step-marker">
                                    {{ index <= trackingStepIndex ? '✓' : '' }}
                                </span>
                                <div class="step-copy">
                                    <strong>{{ step }}</strong>
                                    <span>
                                        {{
                                            index === trackingStepIndex
                                                ? 'Seu pedido está nesta etapa.'
                                                : index < trackingStepIndex
                                                  ? 'Etapa concluída'
                                                  : 'Aguardando atualização'
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <aside class="summary-card card">
                            <h3>Resumo do pedido</h3>
                            <template v-if="latestOrder?.items.length">
                                <div
                                    v-for="item in latestOrder.items"
                                    :key="item.id"
                                    class="summary-line"
                                >
                                    <span
                                        >{{ item.name }} ×
                                        {{ item.quantity }}</span
                                    >
                                    <span>{{
                                        formatPrice(item.price * item.quantity)
                                    }}</span>
                                </div>
                            </template>
                            <div v-else class="summary-line">
                                <span>
                                    {{
                                        latestOrder?.request
                                            ? 'Pedido agendado'
                                            : 'Buttery Pistachio 60 × 2'
                                    }}
                                </span>
                                <span>
                                    {{
                                        latestOrder?.request
                                            ? 'A confirmar'
                                            : formatPrice(60)
                                    }}
                                </span>
                            </div>
                            <div class="summary-line">
                                <span>Total</span>
                                <span>
                                    {{
                                        latestOrder
                                            ? formatPrice(latestOrder.total)
                                            : formatPrice(110)
                                    }}
                                </span>
                            </div>
                            <p class="muted small">
                                {{
                                    latestOrder?.date
                                        ? `Retirada: ${latestOrder.date}${latestOrder.time ? ` às ${latestOrder.time}` : ''}`
                                        : latestOrder?.fulfillment === 'pickup'
                                          ? 'Retirada na loja'
                                          : 'Entrega: Rua do Aroma, 123'
                                }}<br />
                                Previsão: hoje, em breve
                            </p>
                        </aside>
                    </div>
                </div>
            </section>

            <section v-else-if="page === 'checkout'" class="page-wrap">
                <h1 class="page-title">Finalizar compra/pagamento</h1>
                <div class="checkout-grid">
                    <div class="checkout-column">
                        <section class="checkout-card card">
                            <h2>Seu pedido</h2>
                            <div v-if="pendingOrder" class="notice">
                                <strong>Pedido para retirada</strong><br />
                                {{ pendingOrder.name }} ·
                                {{ pendingOrder.date }} às {{ pendingOrder.time
                                }}<br />
                                {{ pendingOrder.message }}
                            </div>
                            <div v-if="cartLines.length" class="table-wrap">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Quantidade</th>
                                            <th>Valor</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="{
                                                product,
                                                quantity,
                                            } in cartLines"
                                            :key="product.id"
                                        >
                                            <td>{{ product.name }}</td>
                                            <td>
                                                <span class="qty-control">
                                                    <button
                                                        type="button"
                                                        :aria-label="`Diminuir ${product.name}`"
                                                        @click="
                                                            changeQuantity(
                                                                product.id,
                                                                -1,
                                                            )
                                                        "
                                                    >
                                                        −
                                                    </button>
                                                    {{ quantity }}
                                                    <button
                                                        type="button"
                                                        :aria-label="`Aumentar ${product.name}`"
                                                        @click="
                                                            changeQuantity(
                                                                product.id,
                                                                1,
                                                            )
                                                        "
                                                    >
                                                        +
                                                    </button>
                                                </span>
                                            </td>
                                            <td>
                                                {{
                                                    formatPrice(
                                                        product.price *
                                                            quantity,
                                                    )
                                                }}
                                            </td>
                                            <td>
                                                <button
                                                    class="remove-item"
                                                    type="button"
                                                    :aria-label="`Remover ${product.name}`"
                                                    @click="
                                                        removeFromCart(
                                                            product.id,
                                                        )
                                                    "
                                                >
                                                    ×
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div
                                v-if="!cartLines.length && !pendingOrder"
                                class="empty-state"
                            >
                                <div class="empty-icon">🛍️</div>
                                <h3>Seu carrinho está vazio</h3>
                                <p>Escolha algo gostoso no nosso cardápio.</p>
                                <button
                                    class="button button-dark"
                                    type="button"
                                    @click="navigate('produtos')"
                                >
                                    Ver produtos
                                </button>
                            </div>
                            <template v-if="cartLines.length || pendingOrder">
                                <div
                                    v-if="cartLines.length"
                                    class="total-block"
                                >
                                    <div class="total-row">
                                        <span>Subtotal</span>
                                        <span>{{ formatPrice(subtotal) }}</span>
                                    </div>
                                    <div class="total-row">
                                        <span>
                                            {{
                                                fulfillment === 'pickup'
                                                    ? 'Retirada na loja'
                                                    : 'Taxa de entrega'
                                            }}
                                        </span>
                                        <span>{{
                                            formatPrice(deliveryFee)
                                        }}</span>
                                    </div>
                                    <div v-if="discount" class="total-row">
                                        <span>Cupom AROMA10</span>
                                        <span
                                            >− {{ formatPrice(discount) }}</span
                                        >
                                    </div>
                                    <form
                                        class="coupon-row"
                                        @submit.prevent="applyCoupon"
                                    >
                                        <input
                                            v-model="coupon"
                                            class="input"
                                            placeholder="Código do cupom"
                                            aria-label="Código do cupom"
                                        />
                                        <button
                                            class="button button-small"
                                            type="submit"
                                        >
                                            Aplicar
                                        </button>
                                    </form>
                                </div>
                                <div
                                    class="total-row final checkout-grand-total"
                                >
                                    <span>Total do pedido</span>
                                    <span>
                                        {{
                                            pendingOrder && !cartLines.length
                                                ? 'A confirmar na cafeteria'
                                                : formatPrice(total)
                                        }}
                                    </span>
                                </div>
                            </template>
                        </section>
                        <section
                            v-if="cartLines.length || pendingOrder"
                            class="checkout-card card"
                        >
                            <h2>Total e resumo</h2>
                            <div class="total-row final">
                                <span>Total final</span>
                                <span>
                                    {{
                                        pendingOrder && !cartLines.length
                                            ? 'A confirmar na cafeteria'
                                            : formatPrice(total)
                                    }}
                                </span>
                            </div>
                            <p class="muted small">
                                {{
                                    pendingOrder && !cartLines.length
                                        ? 'O valor do pedido será confirmado na cafeteria.'
                                        : fulfillment === 'pickup'
                                          ? 'Retirada na loja, sem taxa de entrega.'
                                          : 'Inclui a taxa de entrega.'
                                }}
                            </p>
                            <button
                                class="button"
                                type="button"
                                @click="navigate('produtos')"
                            >
                                Voltar ao cardápio
                            </button>
                        </section>
                    </div>

                    <form
                        v-if="cartLines.length || pendingOrder"
                        class="checkout-column"
                        @submit.prevent="confirmOrder"
                    >
                        <section class="checkout-card card form-grid">
                            <h2>Informações de pagamento</h2>
                            <div class="payment-split">
                                <div class="form-grid">
                                    <div class="field">
                                        <label for="fulfillment-method">
                                            Forma de recebimento
                                        </label>
                                        <select
                                            id="fulfillment-method"
                                            v-model="fulfillment"
                                        >
                                            <option value="pickup">
                                                Retirada na loja
                                            </option>
                                            <option value="delivery">
                                                Entrega
                                            </option>
                                        </select>
                                    </div>
                                    <div class="field">
                                        <label for="payment-method">
                                            Forma de pagamento
                                        </label>
                                        <select
                                            id="payment-method"
                                            v-model="paymentMethod"
                                            @change="handlePaymentMethodChange"
                                        >
                                            <option value="pickup">
                                                Pagar na retirada/entrega
                                            </option>
                                            <option value="pix">
                                                PIX demonstrativo
                                            </option>
                                            <option value="credit">
                                                Cartão de crédito (dados
                                                fictícios)
                                            </option>
                                            <option value="debit">
                                                Cartão de débito (dados
                                                fictícios)
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="pix-box">
                                    <strong>PIX (demonstração)</strong>
                                    <div
                                        class="qr-placeholder"
                                        aria-label="QR ilustrativo"
                                    >
                                        PIX
                                    </div>
                                    <p>QR ilustrativo — não efetua cobrança.</p>
                                    <button
                                        class="button button-small"
                                        type="button"
                                        @click="copyPixKey"
                                    >
                                        Copiar chave PIX
                                    </button>
                                    <div class="payment-options">
                                        <span>VISA</span>
                                        <span>Mastercard</span>
                                        <span>PIX</span>
                                    </div>
                                </div>
                            </div>
                            <div class="notice">
                                Esta é uma demonstração de interface. Não
                                informe dados reais de pagamento: nenhuma
                                cobrança será realizada.
                            </div>
                            <div class="checkout-actions">
                                <button
                                    class="button button-dark"
                                    type="submit"
                                >
                                    {{
                                        pendingOrder && !cartLines.length
                                            ? 'Confirmar pedido'
                                            : `Confirmar pagamento · ${formatPrice(total)}`
                                    }}
                                </button>
                                <button
                                    class="button button-small"
                                    type="button"
                                    @click="navigate('produtos')"
                                >
                                    Voltar ao cardápio
                                </button>
                            </div>
                        </section>
                    </form>
                </div>
            </section>

            <section v-else-if="page === 'sobre'" class="page-wrap">
                <h1 class="page-title">Sobre nós</h1>
                <div class="about-layout">
                    <div
                        class="about-photo"
                        role="img"
                        aria-label="Interior aconchegante do Café Aroma"
                    ></div>
                    <div class="about-copy">
                        <p>
                            O Café Aroma nasceu de um sonho compartilhado em
                            família: unir o frescor do café moído na hora com a
                            doçura artesanal dos nossos cupcakes.
                        </p>
                        <p>
                            Cada grão que escolhemos e cada receita que criamos
                            carregam nossa paixão por proporcionar momentos de
                            pausa e prazer no seu dia.
                        </p>
                        <p>
                            Seja bem-vindo à nossa casa. O café está passado e
                            sempre tem lugar à mesa.
                        </p>
                        <button
                            class="button button-dark"
                            type="button"
                            @click="navigate('contatos')"
                        >
                            Conheça nossa casa
                        </button>
                    </div>
                </div>
            </section>

            <section
                v-else-if="page === 'admin' && !props.auth.user"
                class="page-wrap customer-admin-login"
            >
                <h1 class="page-title">Área administrativa</h1>
                <div class="customer-auth-card card">
                    <p>Entre com sua conta administrativa para continuar.</p>
                    <a class="button button-dark" href="/login">
                        Login administrativo
                    </a>
                </div>
            </section>

            <section v-else class="page-wrap">
                <div class="admin-page-heading">
                    <h1 class="page-title">♙ Dashboard administrativo</h1>
                    <div class="admin-page-actions">
                        <Link
                            class="button button-dark"
                            href="/admin/administradores/cadastro"
                        >
                            Cadastrar administrador
                        </Link>
                        <Link
                            class="button"
                            :href="logout()"
                            method="post"
                            as="button"
                            data-test="admin-logout-button"
                        >
                            Sair
                        </Link>
                    </div>
                </div>
                <div class="admin-grid">
                    <section class="admin-card card">
                        <h2>Relatórios administrativos</h2>
                        <div class="chart">
                            <svg
                                viewBox="0 0 500 150"
                                role="img"
                                aria-label="Gráfico ilustrativo de vendas"
                            >
                                <g stroke="#d2c2ab" stroke-width="1">
                                    <path
                                        d="M45 15H490M45 45H490M45 75H490M45 105H490M45 135H490"
                                    />
                                </g>
                                <g fill="#8b745d" font-size="10">
                                    <text x="2" y="18">R$5k</text>
                                    <text x="2" y="48">R$4k</text>
                                    <text x="2" y="78">R$3k</text>
                                    <text x="2" y="108">R$2k</text>
                                </g>
                                <polyline
                                    points="48,116 118,89 188,100 258,40 328,80 398,68 482,25"
                                    fill="none"
                                    stroke="#493522"
                                    stroke-width="3"
                                />
                                <polyline
                                    points="48,128 118,110 188,118 258,76 328,98 398,91 482,59"
                                    fill="none"
                                    stroke="#977452"
                                    stroke-width="2"
                                    stroke-dasharray="6 5"
                                />
                                <g fill="#493522">
                                    <circle cx="48" cy="116" r="3" />
                                    <circle cx="118" cy="89" r="3" />
                                    <circle cx="188" cy="100" r="3" />
                                    <circle cx="258" cy="40" r="3" />
                                    <circle cx="328" cy="80" r="3" />
                                    <circle cx="398" cy="68" r="3" />
                                    <circle cx="482" cy="25" r="3" />
                                </g>
                                <g fill="#78624e" font-size="10">
                                    <text x="45" y="149">Abril</text>
                                    <text x="114" y="149">Maio</text>
                                    <text x="185" y="149">Junho</text>
                                    <text x="251" y="149">Julho</text>
                                    <text x="320" y="149">Agosto</text>
                                    <text x="390" y="149">Setembro</text>
                                    <text x="465" y="149">Outubro</text>
                                </g>
                            </svg>
                        </div>
                    </section>
                    <section class="admin-card card">
                        <h2>Gestão de estoque</h2>
                        <div class="inventory-row eyebrow">
                            <span>Item</span>
                            <span>Estoque</span>
                            <span>Alerta mín.</span>
                            <span>Ações</span>
                        </div>
                        <div
                            v-for="(item, index) in inventory"
                            :key="`${item.name}-${index}`"
                            class="inventory-row"
                        >
                            <span>{{ item.name }}</span>
                            <span>{{ item.stock }}</span>
                            <span
                                :class="{
                                    'stock-low': item.stock <= item.minimum,
                                }"
                            >
                                {{
                                    item.stock <= item.minimum
                                        ? 'Baixo estoque'
                                        : `mín. ${item.minimum}`
                                }}
                            </span>
                            <span class="admin-actions">
                                <button
                                    type="button"
                                    aria-label="Diminuir estoque"
                                    @click="changeStock(index, -1)"
                                >
                                    −
                                </button>
                                <button
                                    type="button"
                                    aria-label="Aumentar estoque"
                                    @click="changeStock(index, 1)"
                                >
                                    +
                                </button>
                                <button
                                    class="delete-button"
                                    type="button"
                                    :aria-label="`Excluir ${item.name} do estoque`"
                                    @click="removeInventoryItem(index)"
                                >
                                    ×
                                </button>
                            </span>
                        </div>
                        <form
                            class="admin-form"
                            @submit.prevent="addInventoryItem"
                        >
                            <input
                                v-model="inventoryName"
                                placeholder="Produto"
                                aria-label="Nome do produto"
                                required
                            />
                            <input
                                v-model.number="inventoryStock"
                                type="number"
                                min="0"
                                placeholder="Estoque"
                                aria-label="Quantidade em estoque"
                                required
                            />
                            <button class="button button-small" type="submit">
                                Adicionar
                            </button>
                        </form>
                    </section>
                    <section class="admin-card card">
                        <h2>Cadastro de produto</h2>
                        <form
                            class="form-grid"
                            @submit.prevent="addProductToCatalog"
                        >
                            <div class="field">
                                <label for="new-product-name"
                                    >Nome do produto</label
                                >
                                <input
                                    id="new-product-name"
                                    v-model="productForm.name"
                                    placeholder="Ex.: Espresso duplo"
                                    maxlength="191"
                                    required
                                />
                                <span
                                    v-if="productForm.errors.name"
                                    class="field-error"
                                >
                                    {{ productForm.errors.name }}
                                </span>
                            </div>
                            <div class="field">
                                <label for="new-product-price"
                                    >Preço (R$)</label
                                >
                                <input
                                    id="new-product-price"
                                    v-model.number="productForm.price"
                                    type="number"
                                    min="0.01"
                                    step="0.01"
                                    placeholder="15,00"
                                    required
                                />
                                <span
                                    v-if="productForm.errors.price"
                                    class="field-error"
                                >
                                    {{ productForm.errors.price }}
                                </span>
                            </div>
                            <div class="field">
                                <label for="new-product-category"
                                    >Categoria</label
                                >
                                <select
                                    id="new-product-category"
                                    v-model="productForm.category"
                                >
                                    <option value="coffee">Cafés</option>
                                    <option value="cupcakes">Cupcakes</option>
                                    <option value="news">Novidades</option>
                                </select>
                                <span
                                    v-if="productForm.errors.category"
                                    class="field-error"
                                >
                                    {{ productForm.errors.category }}
                                </span>
                            </div>
                            <div class="field">
                                <label for="new-product-image"
                                    >Foto do produto</label
                                >
                                <input
                                    id="new-product-image"
                                    ref="productImageInput"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    :required="!editingProduct"
                                    @change="
                                        productForm.image =
                                            ($event.target as HTMLInputElement)
                                                .files?.[0] ?? null
                                    "
                                />
                                <span class="field-help"
                                    >JPG, PNG ou WEBP, até 5 MB.</span
                                >
                                <span
                                    v-if="productForm.errors.image"
                                    class="field-error"
                                >
                                    {{ productForm.errors.image }}
                                </span>
                            </div>
                            <div class="product-form-actions">
                                <button
                                    class="button button-dark"
                                    type="submit"
                                    :disabled="productForm.processing"
                                >
                                    {{
                                        editingProduct
                                            ? 'Salvar alterações'
                                            : 'Adicionar ao cardápio'
                                    }}
                                </button>
                                <button
                                    v-if="editingProduct"
                                    class="button"
                                    type="button"
                                    @click="cancelProductEdit"
                                >
                                    Cancelar
                                </button>
                            </div>
                        </form>
                    </section>
                    <section class="admin-card card">
                        <h2>Produtos adicionados</h2>
                        <template v-if="customProducts.length">
                            <div
                                class="inventory-row eyebrow product-admin-row"
                            >
                                <span>Produto</span>
                                <span>Categoria</span>
                                <span>Preço</span>
                                <span>Ações</span>
                            </div>
                            <div
                                v-for="product in customProducts"
                                :key="product.id"
                                class="inventory-row product-admin-row"
                            >
                                <span class="product-admin-name">
                                    <img
                                        :src="product.image"
                                        :alt="product.name"
                                    />
                                    {{ product.name }}
                                </span>
                                <span>{{ product.group }}</span>
                                <span>{{ formatPrice(product.price) }}</span>
                                <span class="admin-actions">
                                    <button
                                        type="button"
                                        :aria-label="`Editar ${product.name}`"
                                        @click="editProduct(product)"
                                    >
                                        <Pencil :size="16" />
                                    </button>
                                    <button
                                        class="delete-button"
                                        type="button"
                                        :aria-label="`Excluir ${product.name} do cardápio`"
                                        @click="removeCustomProduct(product)"
                                    >
                                        <Trash2 :size="16" />
                                    </button>
                                </span>
                            </div>
                        </template>
                        <p v-else class="muted small">
                            Ainda não há produtos adicionados ao cardápio.
                        </p>
                    </section>
                    <section class="admin-card card">
                        <h2>Visão geral de pedidos</h2>
                        <div class="order-admin-row eyebrow">
                            <span>Número</span>
                            <span>Nome</span>
                            <span>Status</span>
                            <span>Total</span>
                        </div>
                        <div
                            v-for="(order, index) in adminOrders"
                            :key="order.number"
                            class="order-admin-row"
                        >
                            <span>{{ order.number }}</span>
                            <span>{{ order.name || 'Cliente' }}</span>
                            <select
                                :value="order.status"
                                :aria-label="`Status do pedido ${order.number}`"
                                @change="
                                    updateOrderStatus(
                                        index,
                                        ($event.target as HTMLSelectElement)
                                            .value,
                                    )
                                "
                            >
                                <option
                                    v-for="status in order.fulfillment ===
                                    'pickup'
                                        ? [
                                              'Em Produção',
                                              'Está pronto o pedido',
                                              'Pedido retirado',
                                          ]
                                        : [
                                              'Em Produção',
                                              'Pronto para Entrega',
                                              'Saiu para Entrega',
                                              'Entregue',
                                          ]"
                                    :key="status"
                                >
                                    {{ status }}
                                </option>
                            </select>
                            <span>{{ formatPrice(order.total) }}</span>
                        </div>
                    </section>
                </div>
            </section>
        </main>

        <CafeFooter @navigate="navigate" />
        <div
            class="toast"
            :class="{ visible: toastMessage }"
            role="status"
            aria-live="polite"
        >
            {{ toastMessage }}
        </div>
    </div>
</template>
