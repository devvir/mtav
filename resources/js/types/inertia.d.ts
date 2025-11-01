import '@inertiajs/core';

declare module '@inertiajs/core' {
  interface PageProps {
    flash?: {
      success?: string;
      info?: string;
      warning?: string;
      error?: string;
    };
  }
}
