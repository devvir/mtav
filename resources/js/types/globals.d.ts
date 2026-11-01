import type { SharedProps } from './page';

declare module '@inertiajs/core' {
  export interface InertiaConfig {
    sharedPageProps: SharedProps;
    /** What the server says once (HandleInertiaRequests), kept out of the browser's history */
    flashDataType: {
      success?: string;
      info?: string;
      warning?: string;
      error?: string;
      /** Before running the lottery, it asks (more units than families, say) */
      confirm?: { option: string; message: string };
    };
  }
}

export {};
