import '../css/app.css';

import type { DefineComponent } from 'vue';

import { createInertiaApp, Head, Link } from '@inertiajs/vue3';
import { configureEcho } from '@laravel/echo-vue';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { i18nVue } from 'laravel-vue-i18n';
import { createPinia } from 'pinia';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from './composables/useAppearance';
import { autoRefreshCsrfToken } from './composables/useCsrfToken';

import AppLayout from './layouts/AppLayout.vue';

configureEcho({
    broadcaster: 'reverb',
});

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const pinia = createPinia();

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: async (name) => {
        const page = await resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue')
        );

        // Use AppLayout only if no layout is defined in the Page (set to null for no Layout)
        (typeof page.default.layout === 'undefined') && (page.default.layout = AppLayout);

        return page;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)
            .use(ZiggyVue)
            .use(i18nVue, {
                resolve: (lang: [key: string]) => import(`../../lang/${lang}.json`),
            })
            .component('Link', Link)
            .component('Head', Head)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
        showSpinner: true,
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// Automatically refresh expired CSRF tokens without bothering the user
autoRefreshCsrfToken();
