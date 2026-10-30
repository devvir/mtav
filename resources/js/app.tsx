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
import { setAppName } from '@/lib/app';
import { workOffline } from '@/lib/offline';
import { connectRealtime } from '@/lib/realtime';
import { setRoutes } from '@/lib/route';
import { _, loadLocale } from '@/lib/translations';
import { createInertiaApp } from '@inertiajs/react';
import { Notifications } from '@mantine/notifications';
import type { ComponentType, ReactNode } from 'react';
import { createRoot } from 'react-dom/client';
import { AccountLayout } from './layouts/AccountLayout';
import { AuthLayout } from './layouts/AuthLayout';
import { PlatformLayout } from './layouts/PlatformLayout';
import { ProjectLayout } from './layouts/ProjectLayout';

const appName = import.meta.env.VITE_APP_NAME || 'MTAV';

const PAGES = import.meta.glob<{ default: Page }>('./pages/**/*.tsx', { eager: true });

type Layout = ComponentType<{ children: ReactNode }>;
type Page = ComponentType & { layout?: (page: ReactNode) => ReactNode };

/** Each area has its layout, chosen by the page's folder (a page may set its own) */
const LAYOUTS: [string, Layout][] = [
  ['Auth/', AuthLayout],
  ['Member/', ProjectLayout],
  ['Manage/', ProjectLayout],
  ['Platform/', PlatformLayout],
];

createInertiaApp({
  title: (title) => (title ? `${title} · ${appName}` : appName),
  // Every page comes with the app (it is small): moving between pages never waits for one
  // to load
  resolve: (name) => {
    const page = PAGES[`./pages/${name}.tsx`]!.default;

    if (page.layout === undefined) {
      const Layout = LAYOUTS.find(([prefix]) => name.startsWith(prefix))?.[1] ?? AccountLayout;
      page.layout = (child) => <Layout>{child}</Layout>;
    }

    return page;
  },
  setup({ el, App, props }) {
    setRoutes(props.initialPage.props.routes, props.initialPage.props.ziggy);
    connectRealtime(props.initialPage.props.realtime);
    workOffline(props.initialPage.props.auth?.user?.id ?? null);
    setAppName(props.initialPage.props.name as string | undefined);

    // The first render waits for the words of the language in use (one small file, cached)
    void loadLocale(props.initialPage.props.locale as string).then(() =>
      createRoot(el).render(
        <Appearance closeLabel={_('Close')}>
          <Notifications position="bottom-right" limit={4} />
          <App {...props} />
        </Appearance>,
      ),
    );
  },
  // The bar starts with the click: the sidebar lights the place at once, the bar says it is
  // on its way
  // A link loads its page once the pointer rests on it: passing over others on the way
  // (well under 200 ms each) loads nothing
  defaults: { prefetch: { hoverDelay: 200 } },
  progress: { color: 'var(--mantine-color-brand-filled)', delay: 0 },
});
