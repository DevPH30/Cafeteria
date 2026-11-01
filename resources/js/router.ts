import { createRouter, createWebHistory } from 'vue-router';
import type { RouteRecordRaw } from 'vue-router';

const cafePage = () => import('@/pages/Cafe.vue');

const routes: RouteRecordRaw[] = [
    {
        path: '/',
        name: 'home',
        component: cafePage,
        meta: { page: 'inicio' },
    },
    {
        path: '/produtos',
        name: 'products',
        component: cafePage,
        meta: { page: 'produtos' },
    },
    {
        path: '/sobre-nos',
        name: 'about',
        component: cafePage,
        meta: { page: 'sobre' },
    },
    {
        path: '/contatos',
        name: 'contact',
        component: cafePage,
        meta: { page: 'contatos' },
    },
    {
        path: '/encomendas',
        name: 'orders',
        component: cafePage,
        meta: { page: 'pedidos' },
    },
    {
        path: '/checkout',
        name: 'checkout',
        component: cafePage,
        meta: { page: 'checkout' },
    },
    {
        path: '/acompanhar-pedido',
        name: 'order-tracking',
        component: cafePage,
        meta: { page: 'acompanhar' },
    },
    {
        path: '/minha-conta',
        name: 'customer-account',
        component: cafePage,
        meta: { page: 'conta' },
    },
    {
        path: '/admin',
        name: 'cafe-admin',
        component: cafePage,
        meta: { page: 'admin' },
    },
];

export const cafeRouter = createRouter({
    history: createWebHistory(),
    routes,
});
