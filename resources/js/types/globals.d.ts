// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
  interface ImportMetaEnv {
    readonly VITE_APP_NAME: string;
    [key: string]: string | boolean | undefined;
  }

  interface ImportMeta {
    readonly env: ImportMetaEnv;
    readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
  }
}

export interface AuthUser extends User {
  can: {
    viewAny: Record<AppResource, boolean>;
    create: Record<AppResource, boolean>;
  };
}

export interface Auth {
  user: AuthUser | null;
  verified: boolean;
}

declare module '@inertiajs/core' {
  export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
  };

  interface PageProps extends InertiaPageProps, AppPageProps {}
}

declare module '@vue/runtime-core' {
  interface ComponentCustomProperties {
    $inertia: typeof Router;
    $page: Page;
    $headManager: ReturnType<typeof createHeadManager>;
  }
}
