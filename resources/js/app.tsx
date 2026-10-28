import '@fontsource-variable/bricolage-grotesque';
import '@fontsource-variable/figtree';
import '@mantine/core/styles.css';
import '@mantine/dates/styles.css';
import '@mantine/dropzone/styles.css';
import '@mantine/notifications/styles.css';
import '../css/app.css';
import './design/styles.css';
import './tours';

import { Appearance } from '@/design';
import { connectRealtime } from '@/lib/realtime';
import { setRoutes } from '@/lib/route';
import { setLocale } from '@/lib/translations';
import { createInertiaApp } from '@inertiajs/react';
import { Notifications } from '@mantine/notifications';
import type { ComponentType, ReactNode } from 'react';
import { createRoot } from 'react-dom/client';
import { AccountLayout } from './layouts/AccountLayout';
import { AuthLayout } from './layouts/AuthLayout';
import { ManageLayout } from './layouts/ManageLayout';
import { MemberLayout } from './layouts/MemberLayout';
import { PlatformLayout } from './layouts/PlatformLayout';

const appName = import.meta.env.VITE_APP_NAME || 'MTAV';

type Layout = ComponentType<{ children: ReactNode }>;
type Page = ComponentType & { layout?: (page: ReactNode) => ReactNode };

/** Each area has its layout, chosen by the page's folder (a page may set its own) */
const LAYOUTS: [string, Layout][] = [
  ['Auth/', AuthLayout],
  ['Member/', MemberLayout],
  ['Manage/', ManageLayout],
  ['Platform/', PlatformLayout],
];

createInertiaApp({
  title: (title) => (title ? `${title} · ${appName}` : appName),
  resolve: async (name) => {
    const pages = import.meta.glob<{ default: Page }>('./pages/**/*.tsx');
    const page = (await pages[`./pages/${name}.tsx`]()).default;

    if (page.layout === undefined) {
      const Layout = LAYOUTS.find(([prefix]) => name.startsWith(prefix))?.[1] ?? AccountLayout;
      page.layout = (child) => <Layout>{child}</Layout>;
    }

    return page;
  },
  setup({ el, App, props }) {
    setLocale(props.initialPage.props.locale as string);
    setRoutes(props.initialPage.props.routes, props.initialPage.props.ziggy);
    connectRealtime(props.initialPage.props.realtime);

    createRoot(el).render(
      <Appearance>
        <Notifications position="bottom-right" limit={4} />
        <App {...props} />
      </Appearance>,
    );
  },
  progress: { color: 'var(--mantine-color-brand-filled)', delay: 150 },
});
