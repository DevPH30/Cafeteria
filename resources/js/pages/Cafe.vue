<script setup lang="ts">
import { Head, Link, router as inertiaRouter, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
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
        customer: {
            id: number;
            name: string;
            email: string;
            phone: string;
            address: string;
            neighborhood: string;
            city: string;
            state: string;
            zip_code: string;
        } | null;
    };
    customerStatus?: string | null;
    adminStatus?: string | null;
    catalogProducts?: CafeProduct[];
    latestCustomerOrder?: CafeOrder | null;
    customerOrders?: CafeOrder[];
    adminOrders?: CafeOrder[];
    adminReservations?: CafeReservation[];
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
    createdAt?: string;
    date?: string;
    time?: string;
    message?: string;
    value?: number;
    detail?: string;
    phone?: string;
    request?: boolean;
    paymentMethod?: string;
    deliveryAddress?: string;
    items: OrderItem[];
};
type CafeReservation = {
    number: string;
    name: string;
    email?: string;
    phone?: string;
    status: string;
    date: string;
    time: string;
    message?: string;
    value?: number | null;
    detail?: string | null;
    createdAt?: string;
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
    settings?: {
        deliveryFee: number;
    };
};

const storageKey = 'cafe-aroma-state-v1';
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
    'conta',
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
    conta: 'customer-account',
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
const products = ref<CafeProduct[]>([...(props.catalogProducts ?? [])]);
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
const deliveryAddress = ref('');
const deliveryFeeValue = ref(10);
const paymentMethod = ref('pickup');
const coupon = ref('');
const couponApplied = ref(false);
const toastMessage = ref('');
const loginForm = useForm({
    email: '',
    password: '',
});
const returnToCheckoutAfterLogin = ref(false);
const returnToAccountAfterLogin = ref(false);
const returnToOrdersAfterLogin = ref(false);
const accountTab = ref<'cadastro' | 'pedidos' | 'encomendas'>('cadastro');
const profileForm = useForm({
    name: props.auth.customer?.name ?? '',
    email: props.auth.customer?.email ?? '',
    phone: props.auth.customer?.phone ?? '',
    zip_code: props.auth.customer?.zip_code ?? '',
    neighborhood: props.auth.customer?.neighborhood ?? '',
    city: props.auth.customer?.city ?? '',
    state: props.auth.customer?.state ?? '',
    address: props.auth.customer?.address ?? '',
});
const editingProduct = ref<CafeProduct | null>(null);
const productImageInput = ref<HTMLInputElement | null>(null);
const productForm = useForm({
    name: '',
    description: '',
    price: null as number | null,
    category: 'coffee' as Category,
    image: null as File | null,
});
const stockQuantities = ref<Record<string, number>>({});
let toastTimer: ReturnType<typeof setTimeout> | undefined;

function getCustomerAddress() {
    const customer = props.auth.customer;
    if (!customer) return '';

    return [
        customer.address,
        customer.neighborhood,
        `${customer.city} - ${customer.state}`,
        `CEP ${customer.zip_code}`,
    ].join(', ');
}

watch(
    () => props.auth.customer,
    (customer) => {
        if (customer && !deliveryAddress.value) {
            deliveryAddress.value = getCustomerAddress();
        }
        if (customer) fillProfileForm();
    },
    { immediate: true },
);

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
    cartCount.value > 0 && fulfillment.value === 'delivery'
        ? deliveryFeeValue.value
        : 0,
);
const total = computed(() =>
    Math.max(0, subtotal.value + deliveryFee.value - discount.value),
);
const latestOrder = computed(
    () => props.latestCustomerOrder ?? null,
);
const customerOrders = computed(() =>
    (props.customerOrders ?? []).filter((order) => !order.request),
);
const customerReservations = computed(() =>
    (props.customerOrders ?? []).filter((order) => order.request),
);
const trackingSteps = computed(() =>
    latestOrder.value?.request
        ? ['aguardando retorno', 'pedido confirmado', 'pedido retirado']
        : latestOrder.value?.status === 'Cancelado'
        ? ['Pedido Cancelado']
        : latestOrder.value?.fulfillment === 'pickup'
        ? [
              'Pedido Recebido',
              'Em Produção',
              'Está pronto o pedido',
              'Pedido retirado',
          ]
        : ['Pedido Recebido', 'Em Produção', 'Saiu para Entrega', 'Entregue'],
);
const trackingStatus = computed(() => {
    const status = latestOrder.value?.status;
    if (!status) return '';

    if (latestOrder.value?.request) return status;

    if (latestOrder.value?.fulfillment === 'pickup') {
        if (status === 'Pronto para Entrega') return 'Está pronto o pedido';
        if (status === 'Entregue') return 'Pedido retirado';
    }

    return status === 'Em Preparo' ? 'Em Produção' : status;
});
const trackingStepIndex = computed(() =>
    Math.max(0, trackingSteps.value.indexOf(trackingStatus.value)),
);
function getLocalDate(): string {
    const date = new Date();
    return formatLocalDate(date);
}

function formatLocalDate(date: Date): string {
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${date.getFullYear()}-${month}-${day}`;
}

const selectedOrderDate = ref(getLocalDate());
const orderSort = ref<'newest' | 'oldest'>('newest');
const selectedOrderStatus = ref('');
const orderNumberSearch = ref('');
const selectedReservationDate = ref('');
const reservationSort = ref<'newest' | 'oldest'>('newest');
const selectedReservationStatus = ref('');
const reservationNumberSearch = ref('');
const editingReservation = ref<CafeReservation | null>(null);
const reservationForm = useForm({
    status: '',
    scheduled_date: '',
    scheduled_time: '',
    value: null as number | null,
    detail: '',
});
watch(page, (currentPage, previousPage) => {
    if (currentPage === 'admin' && previousPage !== 'admin') {
        selectedOrderDate.value = getLocalDate();
        orderSort.value = 'newest';
        selectedOrderStatus.value = '';
        orderNumberSearch.value = '';
        selectedReservationDate.value = '';
        reservationSort.value = 'newest';
        selectedReservationStatus.value = '';
        reservationNumberSearch.value = '';
    }
});
const adminReservations = computed(() => {
    const reservations = (props.adminReservations ?? []).filter((reservation) => {
        const matchesDate =
            !selectedReservationDate.value ||
            reservation.date === selectedReservationDate.value;
        const matchesStatus =
            !selectedReservationStatus.value ||
            reservation.status === selectedReservationStatus.value;
        const matchesNumber =
            !reservationNumberSearch.value.trim() ||
            reservation.number.includes(reservationNumberSearch.value.trim());

        return matchesDate && matchesStatus && matchesNumber;
    });

    return [...reservations].sort((first, second) => {
        const firstTime = first.createdAt
            ? new Date(first.createdAt).getTime()
            : 0;
        const secondTime = second.createdAt
            ? new Date(second.createdAt).getTime()
            : 0;

        return reservationSort.value === 'newest'
            ? secondTime - firstTime
            : firstTime - secondTime;
    });
});
const adminOrders = computed(() => {
    const orders = (props.adminOrders ?? []).filter((order) => {
        const matchesDate =
            !selectedOrderDate.value ||
            (order.createdAt &&
                formatLocalDate(new Date(order.createdAt)) ===
                    selectedOrderDate.value);
        const matchesStatus =
            !selectedOrderStatus.value ||
            order.status === selectedOrderStatus.value;
        const matchesNumber =
            !orderNumberSearch.value.trim() ||
            order.number.includes(orderNumberSearch.value.trim());

        return Boolean(matchesDate && matchesStatus && matchesNumber);
    });

    return [...orders].sort((first, second) => {
        const firstTime = first.createdAt
            ? new Date(first.createdAt).getTime()
            : 0;
        const secondTime = second.createdAt
            ? new Date(second.createdAt).getTime()
            : 0;

        return orderSort.value === 'newest'
            ? secondTime - firstTime
            : firstTime - secondTime;
    });
});
const currentYear = new Date().getFullYear();
const monthlyGrossSales = computed(() => {
    const monthLabels = [
        'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun',
        'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez',
    ];
    const totals = monthLabels.map((label, month) => ({
        label,
        month,
        orders: 0,
        reservations: 0,
    }));

    for (const order of props.adminOrders ?? []) {
        if (
            !order.createdAt ||
            order.status === 'Cancelado' ||
            order.status === 'pedido cancelado'
        ) {
            continue;
        }

        const createdAt = new Date(order.createdAt);
        if (createdAt.getFullYear() === currentYear) {
            totals[createdAt.getMonth()].orders += order.total;
        }
    }

    for (const reservation of props.adminReservations ?? []) {
        if (
            !reservation.createdAt ||
            reservation.status === 'pedido cancelado'
        ) {
            continue;
        }

        const createdAt = new Date(reservation.createdAt);
        if (createdAt.getFullYear() === currentYear) {
            totals[createdAt.getMonth()].reservations += reservation.value ?? 0;
        }
    }

    return totals.map((item) => ({
        ...item,
        total: item.orders + item.reservations,
    }));
});
const grossSalesChartMax = computed(() => {
    const highestTotal = Math.max(
        ...monthlyGrossSales.value.map(({ total }) => total),
        0,
    );

    return highestTotal > 0 ? Math.ceil(highestTotal / 100) * 100 : 100;
});
function chartBarX(index: number) {
    return 40 + index * 37;
}
function chartBarHeight(total: number) {
    return (total / grossSalesChartMax.value) * 125;
}
function chartBarY(total: number) {
    return 145 - chartBarHeight(total);
}
function chartLinePoints(
    series: 'orders' | 'reservations',
) {
    return monthlyGrossSales.value
        .map(
            (item) =>
                `${chartBarX(item.month) + 11},${chartBarY(item[series])}`,
        )
        .join(' ');
}
function formatChartValue(value: number) {
    if (value >= 1000) {
        return `R$${(value / 1000).toLocaleString('pt-BR', {
            maximumFractionDigits: 1,
        })}k`;
    }

    return `R$${Math.round(value)}`;
}
const lowStockProducts = computed(() =>
    customProducts.value.filter(
        (product) =>
            (product.stock ?? 0) <= (product.minimumStock ?? 20),
    ),
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
            inventory.value = state.inventory.filter(
                (item): item is InventoryItem =>
                    isInventoryItem(item) && !item.productId,
            );
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
            state.settings &&
            typeof state.settings === 'object' &&
            typeof state.settings.deliveryFee === 'number' &&
            Number.isFinite(state.settings.deliveryFee) &&
            state.settings.deliveryFee >= 0
        ) {
            deliveryFeeValue.value = state.settings.deliveryFee;
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
        settings: {
            deliveryFee: deliveryFeeValue.value,
        },
    };

    try {
        localStorage.setItem(storageKey, JSON.stringify(state));
    } catch (error) {
        console.error('Não foi possível salvar os dados do Café Aroma.', error);
        showToast('Não foi possível salvar as alterações neste navegador.');
    }
}

function navigate(target: string, category?: 'all' | Category) {
    let destination =
        allowedPages.find((candidate) => candidate === target) ?? 'inicio';

    if (category) activeCategory.value = category;
    if (target === 'conta' && !props.auth.customer) {
        returnToAccountAfterLogin.value = true;
        showToast('Entre na sua conta para acessar seus dados.');
        destination = 'inicio';
    }
    if (target === 'pedidos' && !props.auth.customer) {
        returnToOrdersAfterLogin.value = true;
        showToast('Entre na sua conta para fazer uma encomenda.');
        destination = 'inicio';
    }
    searchOpen.value = false;
    void router.push({ name: pageRouteNames[destination] });
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

async function trackOrder(number: string) {
    await router.push({
        name: pageRouteNames.acompanhar,
        query: { order: number },
    });
    inertiaRouter.reload({
        preserveState: false,
        preserveScroll: false,
    });
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

function saveSettings() {
    if (
        !Number.isFinite(deliveryFeeValue.value) ||
        deliveryFeeValue.value < 0 ||
        deliveryFeeValue.value > 100000
    ) {
        showToast('Informe uma taxa de entrega válida.');
        return;
    }

    persistState();
    showToast('Configurações salvas com sucesso.');
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
        onSuccess: () => {
            loginForm.reset('password');
            if (returnToCheckoutAfterLogin.value) {
                returnToCheckoutAfterLogin.value = false;
                navigate('checkout');
            }
            if (returnToAccountAfterLogin.value) {
                returnToAccountAfterLogin.value = false;
                navigate('conta');
            }
            if (returnToOrdersAfterLogin.value) {
                returnToOrdersAfterLogin.value = false;
                navigate('pedidos');
            }
        },
    });
}

function logoutCustomer() {
    inertiaRouter.post('/cliente/logout');
}

function openAccount() {
    if (!props.auth.customer) {
        returnToAccountAfterLogin.value = true;
        showToast('Entre na sua conta para acessar seus dados.');
        navigate('inicio');
        return;
    }

    navigate('conta');
}

function fillProfileForm() {
    const customer = props.auth.customer;
    if (!customer) return;

    profileForm.name = customer.name;
    profileForm.email = customer.email;
    profileForm.zip_code = customer.zip_code;
    profileForm.neighborhood = customer.neighborhood;
    profileForm.city = customer.city;
    profileForm.state = customer.state;
    profileForm.address = customer.address;
}

function submitProfile() {
    profileForm.put('/cliente/perfil', {
        preserveScroll: true,
        onSuccess: () => {
            profileForm.clearErrors();
            showToast('Dados cadastrais atualizados.');
        },
    });
}

function submitPickupRequest(event: SubmitEvent) {
    const form = event.currentTarget;
    if (!(form instanceof HTMLFormElement)) return;

    if (!selectedDate.value) {
        showToast('Escolha uma data no calendário antes de enviar o pedido.');
        return;
    }

    if (!props.auth.customer) {
        returnToOrdersAfterLogin.value = true;
        showToast('Entre na sua conta para fazer uma encomenda.');
        navigate('inicio');
        return;
    }

    const data = new FormData(form);
    inertiaRouter.post('/cliente/pedidos', {
        reservation: true,
        message: String(data.get('message')),
        scheduled_date: selectedDate.value,
        scheduled_time: String(data.get('time')),
    }, {
        preserveScroll: true,
        onSuccess: () => {
            selectedDate.value = null;
            navigate('acompanhar');
        },
        onError: (errors) => {
            const message = Object.values(errors)[0];
            if (message) showToast(String(message));
        },
    });
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

    if (!props.auth.customer) {
        returnToCheckoutAfterLogin.value = true;
        showToast('Entre na sua conta para finalizar o pedido.');
        navigate('inicio');
        return;
    }

    inertiaRouter.post('/cliente/pedidos', {
        items: cartLines.value.map(({ product, quantity }) => ({
            id: Number(product.id),
            quantity,
        })),
        fulfillment: fulfillment.value,
        payment_method: paymentMethod.value,
        delivery_fee: deliveryFee.value,
        address:
            fulfillment.value === 'delivery'
                ? deliveryAddress.value.trim()
                : undefined,
        scheduled_date: pendingOrder.value?.date,
        scheduled_time: pendingOrder.value?.time,
        message: pendingOrder.value?.message,
    }, {
        preserveScroll: true,
        onSuccess: () => {
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
        },
        onError: (errors) => {
            const message = Object.values(errors)[0];
            if (message) showToast(String(message));
        },
    });
}

function changeStock(index: number, amount: number) {
    const item = inventory.value[index];
    if (!item) return;

    item.stock = Math.max(0, item.stock + amount);
    persistState();
}

function updateProductStock(
    product: CafeProduct,
    action: 'add' | 'remove',
    quantityOverride?: number,
) {
    const quantity = quantityOverride ?? stockQuantities.value[product.id] ?? 1;
    if (!Number.isInteger(quantity) || quantity < 1) {
        showToast('Informe uma quantidade inteira maior que zero.');
        return;
    }

    inertiaRouter.patch(
        `/admin/produtos/${product.id}/estoque`,
        { action, quantity },
        {
            preserveScroll: true,
            onSuccess: () => {
                customProducts.value = [...(props.catalogProducts ?? [])];
                products.value = [...customProducts.value];
                stockQuantities.value[product.id] = 1;
            },
        },
    );
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
                    customProducts.value = [...(props.catalogProducts ?? [])];
                    products.value = [...customProducts.value];
                    inventory.value = inventory.value.filter(
                        (item) => !item.productId,
                    );
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
    productForm.description = product.description;
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
            products.value = [...customProducts.value];
            inventory.value = inventory.value.filter(
                (item) => item.productId !== product.id,
            );
            cart.value = cart.value.filter((item) => item.id !== product.id);
            persistState();
        },
    });
}

function updateOrderStatus(order: { number: string }, status: string) {
    if (!order) return;

    inertiaRouter.patch(`/admin/pedidos/${order.number}/status`, { status }, {
        preserveScroll: true,
        onError: (errors) => {
            const message = Object.values(errors)[0];
            if (message) showToast(String(message));
        },
    });
}

function openReservationEditor(reservation: CafeReservation) {
    editingReservation.value = reservation;
    reservationForm.status = reservation.status;
    reservationForm.scheduled_date = reservation.date;
    reservationForm.scheduled_time = reservation.time;
    reservationForm.value = reservation.value ?? null;
    reservationForm.detail = reservation.detail ?? reservation.message ?? '';
    reservationForm.clearErrors();
}

function closeReservationEditor(force = false) {
    if (reservationForm.processing && !force) return;

    editingReservation.value = null;
    reservationForm.reset();
    reservationForm.clearErrors();
}

function saveReservation() {
    if (!editingReservation.value) return;

    reservationForm.patch(
        `/admin/encomendas/${editingReservation.value.number}`,
        {
            preserveScroll: true,
            onSuccess: () => {
                closeReservationEditor(true);
                showToast('Encomenda atualizada com sucesso.');
            },
            onError: (errors) => {
                const message = Object.values(errors)[0];
                if (message) showToast(String(message));
            },
        },
    );
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
                            <span class="category-icon">
                                <img
                                    src="/images/cafe/logo-cup.png"
                                    alt=""
                                    aria-hidden="true"
                                />
                            </span>
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
                            <span class="category-icon">
                                <img
                                    src="/images/cafe/cupcake-icon.png"
                                    alt=""
                                    aria-hidden="true"
                                />
                            </span>
                        </a>
                        <a
                            class="category-card card"
                            href="/encomendas"
                            @click.prevent="navigate('pedidos')"
                        >
                            <div>
                                <strong>Faça sua encomenda</strong>
                                <span>Escolha o melhor horário</span>
                            </div>
                            <span class="category-icon order-category-icon">♡</span>
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
                    {{
                        customProducts.length
                            ? `Nenhum produto encontrado para “${searchQuery}”.`
                            : 'Nenhum produto cadastrado no momento.'
                    }}
                </p>
            </section>

            <section v-else-if="page === 'contatos'" class="page-wrap">
                <h1 class="page-title contact-page-title">
                    <img
                        src="/images/cafe/phone-icon.png"
                        alt=""
                        aria-hidden="true"
                    />
                    <span>Contatos</span>
                </h1>
                <div class="contact-layout">
                    <div class="contact-list">
                        <a
                            class="contact-row"
                            href="https://instagram.com/"
                            target="_blank"
                            rel="noreferrer"
                        >
                            <span class="contact-icon">
                                <img src="/images/cafe/contact-icon-1.png" alt="" aria-hidden="true" />
                            </span>
                            <span>
                                <strong>Instagram</strong>
                                <span>@cafearoma</span>
                            </span>
                        </a>
                        <a class="contact-row" href="tel:+553333333333">
                            <span class="contact-icon">
                                <img src="/images/cafe/contact-icon-2.png" alt="" aria-hidden="true" />
                            </span>
                            <span>
                                <strong>Telefone</strong>
                                <span>+55 (33) 3333-3333</span>
                            </span>
                        </a>
                        <div class="contact-row">
                            <span class="contact-icon">
                                <img src="/images/cafe/contact-icon-3.png" alt="" aria-hidden="true" />
                            </span>
                            <span>
                                <strong>Endereço</strong>
                                <span>Rua do Aroma, 123 — Centro</span>
                            </span>
                        </div>
                        <a
                            class="contact-row"
                            href="mailto:contato@cafearoma.com.br"
                        >
                            <span class="contact-icon">
                                <img src="/images/cafe/contact-icon-4.png" alt="" aria-hidden="true" />
                            </span>
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
                <h1 class="page-title order-page-title">
                    <img
                        src="/images/cafe/handshake-icon.png"
                        alt=""
                        aria-hidden="true"
                    />
                    <span>Encomendas</span>
                </h1>
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
                            <input
                                id="order-time"
                                name="time"
                                type="text"
                                inputmode="numeric"
                                placeholder="Ex.: 14:30"
                                pattern="^([01]\d|2[0-3]):[0-5]\d$"
                                title="Informe o horário no formato HH:MM."
                                required
                            />
                        </div>
                        <button class="button button-dark" type="submit">
                            Solicitar encomenda
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
                <h1 class="page-title tracking-page-title">
                    <img
                        src="/images/cafe/scooter-icon.png"
                        alt=""
                        aria-hidden="true"
                    />
                    <span>Acompanhar pedido</span>
                </h1>
                <div v-if="latestOrder" class="tracking-panel card">
                    <div v-if="latestOrder.request" class="notice">
                        Entraremos em contato em até 2h pelo telefone fornecido
                        no cadastro do cliente:
                        <strong>{{ latestOrder.phone }}</strong>
                    </div>
                    <div class="tracking-top">
                        <div>
                            <span class="eyebrow">Café Aroma</span>
                            <h2>
                                Status do pedido #{{ latestOrder.number }}
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
                            <p v-if="latestOrder.request">
                                <strong>Mensagem:</strong>
                                {{ latestOrder.message }}
                            </p>
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
                                <span>Itens do pedido não disponíveis</span>
                            </div>
                            <div class="summary-line">
                                <span>Total</span>
                                <span>{{ formatPrice(latestOrder.total) }}</span>
                            </div>
                            <p class="muted small">
                                {{
                                    latestOrder.date
                                        ? `Retirada: ${latestOrder.date}${latestOrder.time ? ` às ${latestOrder.time}` : ''}`
                                        : latestOrder.fulfillment === 'pickup'
                                          ? 'Retirada na loja'
                                          : `Entrega: ${latestOrder.deliveryAddress ?? 'Endereço informado no pedido'}`
                                }}
                            </p>
                        </aside>
                    </div>
                </div>
                <div v-else class="empty-state card">
                    <h2>Nenhum pedido realizado</h2>
                    <p>Faça um pedido para acompanhar o status por aqui.</p>
                    <button
                        class="button button-dark"
                        type="button"
                        @click="navigate('produtos')"
                    >
                        Ver produtos
                    </button>
                </div>
            </section>

            <section v-else-if="page === 'checkout'" class="page-wrap">
                <h1 class="page-title checkout-page-title">
                    <img
                        src="/images/cafe/cart-icon.png"
                        alt=""
                        aria-hidden="true"
                    />
                    <span>Finalizar compra/pagamento</span>
                </h1>
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
                            <div
                                v-if="fulfillment === 'delivery' && props.auth.customer"
                                class="field"
                            >
                                <label for="delivery-address">
                                    Endereço de entrega
                                </label>
                                <input
                                    id="delivery-address"
                                    v-model="deliveryAddress"
                                    maxlength="700"
                                    required
                                />
                                <span class="field-help">
                                    Você pode alterar o endereço para esta entrega.
                                </span>
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

            <section v-else-if="page === 'conta'" class="page-wrap customer-account">
                <template v-if="!props.auth.customer">
                    <h1 class="page-title">Minha conta</h1>
                    <div class="customer-auth-card card">
                        <p>Entre na sua conta para acessar seus dados e pedidos.</p>
                        <button class="button button-dark" type="button" @click="navigate('inicio')">
                            Acessar login
                        </button>
                    </div>
                </template>
                <template v-else>
                <div class="account-heading">
                    <div>
                        <span class="eyebrow">área do cliente</span>
                        <h1 class="page-title">Minha conta</h1>
                    </div>
                    <button class="button" type="button" @click="logoutCustomer">
                        Sair da conta
                    </button>
                </div>
                <div class="account-tabs" role="tablist" aria-label="Seções da conta">
                    <button
                        v-for="tab in [
                            { id: 'cadastro', label: 'Cadastro' },
                            { id: 'pedidos', label: 'Pedidos' },
                            { id: 'encomendas', label: 'Encomendas' },
                        ]"
                        :key="tab.id"
                        class="account-tab"
                        :class="{ active: accountTab === tab.id }"
                        type="button"
                        role="tab"
                        :aria-selected="accountTab === tab.id"
                        @click="
                            accountTab = tab.id as
                                'cadastro' | 'pedidos' | 'encomendas'
                        "
                    >
                        {{ tab.label }}
                    </button>
                </div>
                <section v-if="accountTab === 'cadastro'" class="account-panel card">
                    <h2>Dados cadastrais</h2>
                    <form class="form-grid" @submit.prevent="submitProfile">
                        <div class="field">
                            <label for="profile-name">Nome</label>
                            <input id="profile-name" v-model="profileForm.name" required />
                            <span v-if="profileForm.errors.name" class="customer-field-error">{{ profileForm.errors.name }}</span>
                        </div>
                        <div class="field">
                            <label for="profile-email">E-mail</label>
                            <input id="profile-email" v-model="profileForm.email" type="email" required />
                            <span v-if="profileForm.errors.email" class="customer-field-error">{{ profileForm.errors.email }}</span>
                        </div>
                        <div class="field">
                            <label for="profile-phone">Telefone</label>
                            <input id="profile-phone" v-model="profileForm.phone" required />
                            <span v-if="profileForm.errors.phone" class="customer-field-error">{{ profileForm.errors.phone }}</span>
                        </div>
                        <div class="field">
                            <label for="profile-zip">CEP</label>
                            <input id="profile-zip" v-model="profileForm.zip_code" required />
                            <span v-if="profileForm.errors.zip_code" class="customer-field-error">{{ profileForm.errors.zip_code }}</span>
                        </div>
                        <div class="field">
                            <label for="profile-address">Endereço</label>
                            <input id="profile-address" v-model="profileForm.address" required />
                        </div>
                        <div class="field">
                            <label for="profile-neighborhood">Bairro</label>
                            <input id="profile-neighborhood" v-model="profileForm.neighborhood" required />
                        </div>
                        <div class="field">
                            <label for="profile-city">Cidade</label>
                            <input id="profile-city" v-model="profileForm.city" required />
                        </div>
                        <div class="field">
                            <label for="profile-state">Estado</label>
                            <input id="profile-state" v-model="profileForm.state" maxlength="2" required />
                        </div>
                        <button class="button button-dark" type="submit" :disabled="profileForm.processing">
                            {{ profileForm.processing ? 'Salvando...' : 'Salvar alterações' }}
                        </button>
                    </form>
                </section>
                <section v-else-if="accountTab === 'pedidos'" class="account-panel card">
                    <h2>Pedidos realizados</h2>
                    <div v-if="customerOrders.length" class="customer-orders">
                        <div v-for="order in customerOrders" :key="order.number" class="customer-order-row">
                            <div>
                                <strong>Pedido #{{ order.number }}</strong>
                                <span class="muted small">{{ order.status }}</span>
                            </div>
                            <strong>{{ formatPrice(order.total) }}</strong>
                            <button class="button button-small" type="button" @click="trackOrder(order.number)">
                                Acompanhar
                            </button>
                        </div>
                    </div>
                    <p v-else class="muted">Você ainda não realizou pedidos.</p>
                </section>
                <section v-else class="account-panel card">
                    <h2>Encomendas</h2>
                    <div v-if="customerReservations.length" class="customer-orders">
                        <article
                            v-for="order in customerReservations"
                            :key="order.number"
                            class="customer-order-row"
                        >
                            <div>
                                <strong>Encomenda #{{ order.number }}</strong>
                                <span class="muted small">
                                    Status: {{ order.status }}
                                </span>
                                <span class="muted small">
                                    Cliente: {{ order.name }} ·
                                    {{ order.phone }}
                                </span>
                            </div>
                            <div>
                                <span class="muted small">
                                    Retirada:
                                    {{ order.date }} às {{ order.time }}
                                </span>
                                <span class="muted small">
                                    Mensagem: {{ order.message }}
                                </span>
                            </div>
                            <button
                                class="button button-small"
                                type="button"
                                @click="trackOrder(order.number)"
                            >
                                Acompanhar
                            </button>
                        </article>
                    </div>
                    <p v-else class="muted">
                        Você ainda não realizou nenhuma encomenda.
                    </p>
                </section>
                </template>
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
                    <div class="admin-page-title-group">
                        <h1 class="page-title">
                            <img
                                src="/images/cafe/admin-lock-icon.png"
                                alt=""
                                aria-hidden="true"
                            />
                            <span>Dashboard administrativo</span>
                        </h1>
                        <p class="admin-user-name">
                            Usuário: {{ props.auth.user?.name }}
                        </p>
                    </div>
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
                    <section class="admin-card card reports-admin-card">
                        <h2>Vendas brutas por mês · {{ currentYear }}</h2>
                        <div class="chart">
                            <svg
                                viewBox="0 0 500 180"
                                role="img"
                                :aria-label="`Vendas brutas por mês em ${currentYear}`"
                            >
                                <g stroke="#d2c2ab" stroke-width="1">
                                    <path
                                        d="M40 20H490M40 51H490M40 82H490M40 113H490M40 145H490"
                                    />
                                </g>
                                <g fill="#8b745d" font-size="10">
                                    <text x="0" y="23">
                                        {{ formatChartValue(grossSalesChartMax) }}
                                    </text>
                                    <text x="0" y="54">
                                        {{
                                            formatChartValue(
                                                grossSalesChartMax * 0.75,
                                            )
                                        }}
                                    </text>
                                    <text x="0" y="86">
                                        {{ formatChartValue(grossSalesChartMax / 2) }}
                                    </text>
                                    <text x="0" y="117">
                                        {{
                                            formatChartValue(
                                                grossSalesChartMax * 0.25,
                                            )
                                        }}
                                    </text>
                                    <text x="17" y="149">R$0</text>
                                </g>
                                <polyline
                                    :points="chartLinePoints('orders')"
                                    fill="none"
                                    stroke="#493522"
                                    stroke-width="3"
                                />
                                <polyline
                                    :points="chartLinePoints('reservations')"
                                    fill="none"
                                    stroke="#977452"
                                    stroke-width="3"
                                />
                                <g
                                    v-for="item in monthlyGrossSales"
                                    :key="item.month"
                                >
                                    <circle
                                        class="chart-point orders"
                                        :cx="chartBarX(item.month) + 11"
                                        :cy="chartBarY(item.orders)"
                                        r="5.5"
                                        fill="#493522"
                                    >
                                        <title>
                                            {{ item.label }} · Pedidos:
                                            {{ formatPrice(item.orders) }}
                                        </title>
                                    </circle>
                                    <circle
                                        class="chart-point reservations"
                                        :cx="chartBarX(item.month) + 11"
                                        :cy="chartBarY(item.reservations)"
                                        r="5.5"
                                        fill="#977452"
                                    >
                                        <title>
                                            {{ item.label }} · Encomendas:
                                            {{ formatPrice(item.reservations) }}
                                        </title>
                                    </circle>
                                    <text
                                        :x="chartBarX(item.month) + 11"
                                        y="163"
                                        text-anchor="middle"
                                        fill="#78624e"
                                        font-size="9"
                                    >
                                        {{ item.label }}
                                    </text>
                                </g>
                            </svg>
                        </div>
                        <div class="chart-legend" aria-label="Legenda do gráfico">
                            <span>
                                <i class="chart-legend-swatch orders"></i>
                                Pedidos
                            </span>
                            <span>
                                <i class="chart-legend-swatch reservations"></i>
                                Encomendas
                            </span>
                        </div>
                        <p
                            v-if="!monthlyGrossSales.some((item) => item.total)"
                            class="muted small"
                        >
                            Ainda não há vendas registradas neste ano.
                        </p>
                    </section>
                    <section class="admin-card card settings-admin-card">
                        <h2>Configurações</h2>
                        <form
                            class="form-grid"
                            @submit.prevent="saveSettings"
                        >
                            <div class="field">
                                <label for="delivery-fee">
                                    Taxa de entrega (R$)
                                </label>
                                <input
                                    id="delivery-fee"
                                    v-model.number="deliveryFeeValue"
                                    type="number"
                                    min="0"
                                    max="100000"
                                    step="0.01"
                                    required
                                />
                                <span class="field-help">
                                    Valor aplicado aos pedidos com entrega.
                                </span>
                            </div>
                            <button
                                class="button button-dark"
                                type="submit"
                            >
                                Salvar configurações
                            </button>
                        </form>
                    </section>
                    <section class="admin-card card stock-admin-card">
                        <h2>Gestão de estoque</h2>
                        <div
                            v-if="lowStockProducts.length"
                            class="stock-alert"
                            role="alert"
                        >
                            <strong>Alerta de estoque mínimo:</strong>
                            {{ lowStockProducts.length }} produto(s) precisam
                            de reposição.
                        </div>
                        <div class="inventory-row eyebrow">
                            <span>Item</span>
                            <span>Estoque</span>
                            <span>Quantidade mínima</span>
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
                        <div
                            v-for="product in customProducts"
                            :key="`catalog-stock-${product.id}`"
                            class="inventory-row"
                        >
                            <span>{{ product.name }}</span>
                            <span>{{ product.stock ?? 0 }}</span>
                            <span
                                :class="{
                                    'stock-low':
                                        (product.stock ?? 0) <=
                                        (product.minimumStock ?? 20),
                                }"
                            >
                                {{
                                    (product.stock ?? 0) <=
                                    (product.minimumStock ?? 20)
                                        ? 'Baixo estoque'
                                        : `mín. ${product.minimumStock ?? 20}`
                                }}
                            </span>
                            <span class="admin-actions">
                                <button
                                    type="button"
                                    aria-label="Adicionar uma unidade ao estoque"
                                    @click="
                                        updateProductStock(product, 'add', 1)
                                    "
                                >
                                    +
                                </button>
                                <button
                                    type="button"
                                    aria-label="Remover uma unidade do estoque"
                                    @click="
                                        updateProductStock(product, 'remove', 1)
                                    "
                                >
                                    −
                                </button>
                            </span>
                        </div>
                    </section>
                    <section class="admin-card card product-form-admin-card">
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
                                <label for="new-product-description"
                                    >Descrição</label
                                >
                                <textarea
                                    id="new-product-description"
                                    v-model="productForm.description"
                                    maxlength="150"
                                    placeholder="Descreva o produto"
                                    rows="3"
                                    required
                                ></textarea>
                                <span class="field-help"
                                    >Até 150 caracteres.</span
                                >
                                <span
                                    v-if="productForm.errors.description"
                                    class="field-error"
                                >
                                    {{ productForm.errors.description }}
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
                    <section class="admin-card card products-admin-card">
                        <h2>Produtos adicionados</h2>
                        <template v-if="customProducts.length">
                            <div
                                class="inventory-row eyebrow product-admin-row"
                            >
                                <span>Produto</span>
                                <span>Categoria</span>
                                <span>Estoque</span>
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
                                <span
                                    :class="{
                                        'stock-low':
                                            (product.stock ?? 0) <=
                                            (product.minimumStock ?? 20),
                                    }"
                                >
                                    {{ product.stock ?? 0 }}
                                    <small>
                                        (mín. {{ product.minimumStock ?? 20 }})
                                    </small>
                                </span>
                                <span class="admin-actions">
                                    <input
                                        v-model.number="
                                            stockQuantities[product.id]
                                        "
                                        class="stock-quantity"
                                        type="number"
                                        min="1"
                                        aria-label="Quantidade para movimentar"
                                    />
                                    <button
                                        class="button button-small"
                                        type="button"
                                        :aria-label="`Adicionar ${product.name} ao estoque`"
                                        @click="
                                            updateProductStock(product, 'add')
                                        "
                                    >
                                        + estoque
                                    </button>
                                    <button
                                        class="button button-small"
                                        type="button"
                                        :aria-label="`Remover ${product.name} do estoque`"
                                        @click="
                                            updateProductStock(product, 'remove')
                                        "
                                    >
                                        − estoque
                                    </button>
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
                    <section class="admin-card card orders-admin-card">
                        <h2>Visão geral de pedidos</h2>
                        <div class="order-admin-filters">
                            <label>
                                Data
                                <input
                                    v-model="selectedOrderDate"
                                    type="date"
                                    aria-label="Filtrar pedidos por data"
                                />
                            </label>
                            <label>
                                Status
                                <select
                                    v-model="selectedOrderStatus"
                                    aria-label="Filtrar pedidos por status"
                                >
                                    <option value="">Todos</option>
                                    <option value="Em Produção">
                                        Em Produção
                                    </option>
                                    <option value="Pronto para Entrega">
                                        Pronto para Entrega
                                    </option>
                                    <option value="Está pronto o pedido">
                                        Está pronto o pedido
                                    </option>
                                    <option value="Saiu para Entrega">
                                        Saiu para Entrega
                                    </option>
                                    <option value="Entregue">Entregue</option>
                                    <option value="Pedido retirado">
                                        Pedido retirado
                                    </option>
                                    <option value="Cancelado">Cancelado</option>
                                </select>
                            </label>
                            <label>
                                Número do pedido
                                <input
                                    v-model="orderNumberSearch"
                                    type="search"
                                    inputmode="numeric"
                                    placeholder="Digite o número"
                                    aria-label="Buscar por número do pedido"
                                />
                            </label>
                            <label>
                                Ordenar
                                <select
                                    v-model="orderSort"
                                    aria-label="Ordenar pedidos por data de criação"
                                >
                                    <option value="newest">
                                        Mais recentes primeiro
                                    </option>
                                    <option value="oldest">
                                        Mais antigos primeiro
                                    </option>
                                </select>
                            </label>
                        </div>
                        <div class="order-admin-row eyebrow">
                            <span>Número</span>
                            <span>Nome</span>
                            <span>Status</span>
                            <span>Total</span>
                        </div>
                        <div
                            v-for="order in adminOrders"
                            :key="order.number"
                            class="order-admin-row"
                        >
                            <span>{{ order.number }}</span>
                            <span>{{ order.name }}<br /><small>{{ order.email }}</small></span>
                            <select
                                :value="order.status"
                                :aria-label="`Status do pedido ${order.number}`"
                                @change="
                                    updateOrderStatus(
                                        order,
                                        ($event.target as HTMLSelectElement)
                                            .value,
                                    )
                                "
                            >
                                <option
                                    v-for="status in order.request
                                        ? [
                                              'aguardando retorno',
                                              'pedido confirmado',
                                              'pedido retirado',
                                              'pedido cancelado',
                                          ]
                                        : order.fulfillment === 'pickup'
                                        ? [
                                              'Em Produção',
                                              'Está pronto o pedido',
                                              'Pedido retirado',
                                              'Cancelado',
                                          ]
                                        : [
                                              'Em Produção',
                                              'Pronto para Entrega',
                                              'Saiu para Entrega',
                                              'Entregue',
                                              'Cancelado',
                                          ]"
                                    :key="status"
                                >
                                    {{ status }}
                                </option>
                            </select>
                            <span>{{ formatPrice(order.total) }}</span>
                        </div>
                        <p v-if="!adminOrders.length" class="muted small">
                            Ainda não há pedidos realizados.
                        </p>
                    </section>
                    <section class="admin-card card reservations-admin-card">
                        <h2>Visão geral de encomendas</h2>
                        <div class="order-admin-filters">
                            <label>
                                Data
                                <input
                                    v-model="selectedReservationDate"
                                    type="date"
                                    aria-label="Filtrar encomendas por data"
                                />
                            </label>
                            <label>
                                Status
                                <select v-model="selectedReservationStatus">
                                    <option value="">Todos</option>
                                    <option value="aguardando retorno">Aguardando retorno</option>
                                    <option value="pedido confirmado">Pedido confirmado</option>
                                    <option value="pedido retirado">Pedido retirado</option>
                                    <option value="pedido cancelado">Pedido cancelado</option>
                                </select>
                            </label>
                            <label>
                                Número da encomenda
                                <input
                                    v-model="reservationNumberSearch"
                                    type="search"
                                    inputmode="numeric"
                                    placeholder="Digite o número"
                                />
                            </label>
                            <label>
                                Ordenar
                                <select v-model="reservationSort">
                                    <option value="newest">Mais recentes primeiro</option>
                                    <option value="oldest">Mais antigas primeiro</option>
                                </select>
                            </label>
                        </div>
                        <div class="order-admin-row eyebrow">
                            <span>Número</span>
                            <span>Cliente</span>
                            <span>Status</span>
                            <span>Ações</span>
                        </div>
                        <div
                            v-for="reservation in adminReservations"
                            :key="reservation.number"
                            class="order-admin-row"
                        >
                            <span>{{ reservation.number }}</span>
                            <span>
                                {{ reservation.name }}<br />
                                <small>{{ reservation.phone }}</small>
                            </span>
                            <select
                                :value="reservation.status"
                                :aria-label="`Status da encomenda ${reservation.number}`"
                                @change="
                                    updateOrderStatus(
                                        reservation,
                                        ($event.target as HTMLSelectElement).value,
                                    )
                                "
                            >
                                <option value="aguardando retorno">Aguardando retorno</option>
                                <option value="pedido confirmado">Pedido confirmado</option>
                                <option value="pedido retirado">Pedido retirado</option>
                                <option value="pedido cancelado">Pedido cancelado</option>
                            </select>
                            <button
                                class="button button-small"
                                type="button"
                                @click="openReservationEditor(reservation)"
                            >
                                Editar
                            </button>
                        </div>
                        <p v-if="!adminReservations.length" class="muted small">
                            Ainda não há encomendas realizadas.
                        </p>
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
        <div
            v-if="editingReservation"
            class="reservation-modal-backdrop"
            role="presentation"
            @click.self="closeReservationEditor"
        >
            <section
                class="reservation-modal card"
                role="dialog"
                aria-modal="true"
                aria-labelledby="reservation-modal-title"
            >
                <div class="reservation-modal-header">
                    <h2 id="reservation-modal-title">
                        Editar encomenda #{{ editingReservation.number }}
                    </h2>
                    <button
                        type="button"
                        aria-label="Fechar edição"
                        @click="closeReservationEditor"
                    >
                        ×
                    </button>
                </div>
                <form class="form-grid" @submit.prevent="saveReservation">
                    <div class="field">
                        <label for="reservation-status">Status</label>
                        <select id="reservation-status" v-model="reservationForm.status">
                            <option value="aguardando retorno">Aguardando retorno</option>
                            <option value="pedido confirmado">Pedido confirmado</option>
                            <option value="pedido retirado">Pedido retirado</option>
                            <option value="pedido cancelado">Pedido cancelado</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="reservation-date">Data de retirada</label>
                        <input id="reservation-date" v-model="reservationForm.scheduled_date" type="date" required />
                    </div>
                    <div class="field">
                        <label for="reservation-time">Horário de retirada</label>
                        <input id="reservation-time" v-model="reservationForm.scheduled_time" type="text" pattern="^([01]\d|2[0-3]):[0-5]\d$" required />
                    </div>
                    <div class="field">
                        <label for="reservation-value">Valor</label>
                        <input id="reservation-value" v-model.number="reservationForm.value" type="number" min="0" step="0.01" />
                    </div>
                    <div class="field">
                        <label for="reservation-detail">Detalhamento</label>
                        <textarea id="reservation-detail" v-model="reservationForm.detail" maxlength="2000"></textarea>
                    </div>
                    <div class="reservation-modal-actions">
                        <button class="button" type="button" @click="closeReservationEditor">
                            Cancelar
                        </button>
                        <button class="button button-dark" type="submit" :disabled="reservationForm.processing">
                            {{ reservationForm.processing ? 'Salvando...' : 'Salvar alterações' }}
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</template>
