import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import CustomerAuthLayout from '@/layouts/CustomerAuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';
import { router as inertiaRouter } from '@inertiajs/vue3';
import { cafeRouter } from '@/router';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

inertiaRouter.on('navigate', (event) => {
    const url = new URL(event.detail.page.url, window.location.origin);
    const destination = `${url.pathname}${url.search}${url.hash}`;

    if (cafeRouter.currentRoute.value.fullPath !== destination) {
        void cafeRouter.replace(destination);
    }
});

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
            case name === 'Cafe':
                return null;
            case name.startsWith('customer/'):
                return CustomerAuthLayout;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    withApp: (app) => {
        app.use(cafeRouter);
        app.directive('focus', {
            mounted: (el: HTMLElement, shouldFocus) => {
                if (shouldFocus.value !== false) {
                    el.focus();
                }
            },
        });
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
