import type { SharedProps } from './page';

declare module '@inertiajs/core' {
  export interface InertiaConfig {
    sharedPageProps: SharedProps;
  }
}

export {};
