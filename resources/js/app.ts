import '../css/app.css';
import './tours';

import { realtimeEnabled } from '@/composables/useRealtime';
import { route } from '@/composables/useRoute';
import { initializeTheme } from '@/composables/useTheme';
import { _, setLocale } from '@/composables/useTranslations';
import { autoAnimatePlugin } from '@formkit/auto-animate/vue';
import { createInertiaApp, Link } from '@inertiajs/vue3';
import { configureEcho } from '@laravel/echo-vue';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h, type DefineComponent } from 'vue';
import AccountLayout from './layouts/AccountLayout.vue';
import AuthLayout from './layouts/AuthLayout.vue';
import ManageLayout from './layouts/ManageLayout.vue';
import MemberLayout from './layouts/MemberLayout.vue';
import PlatformLayout from './layouts/PlatformLayout.vue';

const appName = import.meta.env.VITE_APP_NAME || 'MTAV';

/**
 * Each area has its layout, chosen by the page's folder (pages may set their own).
 */
const layouts: [string, DefineComponent][] = [
  ['Auth/', AuthLayout as DefineComponent],
  ['Member/', MemberLayout as DefineComponent],
  ['Manage/', ManageLayout as DefineComponent],
  ['Platform/', PlatformLayout as DefineComponent],
];

createInertiaApp({
  title: (title) => (title ? `${title} · ${appName}` : appName),
  resolve: async (name) => {
    const page = await resolvePageComponent(
      `./pages/${name}.vue`,
      import.meta.glob<DefineComponent>('./pages/**/*.vue'),
    );

    if (page.default.layout === undefined) {
      page.default.layout =
        name === 'Error'
          ? (_h: typeof h, child: { props: { project?: unknown; auth?: { user?: unknown } } }) =>
              h(
                child.props.project
                  ? MemberLayout
                  : child.props.auth?.user
                    ? AccountLayout
                    : AuthLayout,
                () => child,
              )
          : (layouts.find(([prefix]) => name.startsWith(prefix))?.[1] ?? AccountLayout);
    }

    return page;
  },
  setup({ el, App, props, plugin }) {
    setLocale(props.initialPage.props.locale as string);

    const app = createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(autoAnimatePlugin)
      .component('Link', Link);

    app.config.globalProperties.route = route;
    app.config.globalProperties._ = _;

    app.mount(el);
  },
  progress: { color: 'var(--interactive)', delay: 150 },
});

initializeTheme();

if (realtimeEnabled()) {
  configureEcho({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: import.meta.env.VITE_REVERB_SCHEME === 'https',
    enabledTransports: ['ws', 'wss'],
  });
}
