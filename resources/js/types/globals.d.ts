import type { SharedProps } from './page';

declare module '@inertiajs/core' {
  // eslint-disable-next-line @typescript-eslint/no-empty-object-type -- augments Inertia's PageProps
  interface PageProps extends SharedProps {}
}

declare module 'vue' {
  interface ComponentCustomProperties {
    route: typeof import('@/composables/useRoute').route;
    _: typeof import('@/composables/useTranslations')._;
  }
}

export {};
