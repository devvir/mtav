import '@inertiajs/core';

export interface AuthUser extends User {
  projects: ApiResource<Project>[];
  can: {
    viewAny: Record<AppEntityNS, boolean>;
    create: Record<AppEntityNS, boolean>;
  };
}

export interface Auth {
  user: AuthUser | null;
  projects?: ApiResource<Project>[];
  notifications: {
    recent: Notification[];
    unread: number;
  }
}

export interface FlashProps {
  success: string | null;
  info: string | null;
  warning: string | null;
  error: string | null;
}

declare module '@inertiajs/core' {
  interface PageProps extends InertiaPageProps, AppPageProps, TransientPageProps {}

  export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    auth: Auth;
    ziggy: Config & { location: string };
  };

  interface TransientPageProps {
    route: string | null;
    project: ApiResource<Project> | null;
    flash: FlashProps;
  }
}
