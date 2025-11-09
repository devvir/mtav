import '@inertiajs/core';

export interface AuthUser extends User {
  can: {
    viewAny: Record<AppResource, boolean>;
    create: Record<AppResource, boolean>;
  };
};

export interface Auth {
  user: AuthUser | null;
  verified: boolean;
};

export interface StateProps {
  route: string;
  project: ApiResource<Project> | null;
  groupMembers: boolean;
};

export interface FlashProps {
  success: string | null;
  info: string | null;
  warning: string | null;
  error: string | null;
};

declare module '@inertiajs/core' {
  interface PageProps extends InertiaPageProps, AppPageProps, TransientPageProps {}

  export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    auth: Auth;
    ziggy: Config & { location: string };
  };

  interface TransientPageProps {
    state: StateProps;
    flash: FlashProps;
    sidebarOpen: boolean;
  }
}
