import '../css/app.css';

import { autoAnimatePlugin } from '@formkit/auto-animate/vue';
import { createInertiaApp, Link } from '@inertiajs/vue3';
import { putConfig, renderApp } from '@inertiaui/modal-vue';
import { configureEcho } from '@laravel/echo-vue';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createPinia } from 'pinia';
import { createApp } from 'vue';
import VueKonva from 'vue-konva';
import { ZiggyVue } from 'ziggy-js';
import { autoRefreshCsrfToken } from './composables/useCsrfToken';
import { initializeTheme } from './composables/useAppearance';
import AppSidebarLayout from './layouts/app/AppSidebarLayout.vue';

const appName = import.meta.env.VITE_APP_NAME || 'MTAV';

const pinia = createPinia();

createInertiaApp({
  title: (title) => (title ? `${title} - ${appName}` : appName),
  resolve: async (name) => {
    const page = await resolvePageComponent(
      `./pages/${name}.vue`,
      import.meta.glob<Component>('./pages/**/*.vue'),
    );

    // Use default only if no layout is defined in the Page (use null for no layout)
    if (typeof page.default.layout === 'undefined') {
      page.default.layout = AppSidebarLayout;
    }

    return page;
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: renderApp(App, props) })
      .use(plugin)
      .use(pinia)
      .use(ZiggyVue)
      .use(VueKonva)
      .use(autoAnimatePlugin)
      .component('Link', Link)
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

configureEcho({
  broadcaster: 'reverb',
});

putConfig({
  type: 'modal',
  modal: {
    panelClasses: 'modalPanel',
    paddingClasses: 'modalPadding',
  },
  slideover: {
    maxWidth: 'xl',
    panelClasses: 'modalPanel modalSlideover',
    paddingClasses: 'modalPadding',
  },
});

/*
const renderApp = (App, props) => {
    if (props.resolveComponent) {
        resolveComponent = props.resolveComponent
    }

    return () => h(ModalRoot, () => h(App, props))
}

TODO : overwrite renderApp to automatically wrap the page in a Modal
       No configuration is required, since the modal config can be passed as props
       to the ModalLink, and/or set with putConfig(). Event listeners can probably
       be set using useModal(), and if not... that can also be arranged. For example,
       having control of the render function, we could emit the events in the root
       of our Component directly (i.e. the root of the page loaded in the modal).
       Or, we can add a reference to the Modal in the props, so we can do something
       like props.modal.on('close'), props.modal.on('message'), etc.
*/
