import type { Id } from './models';

/**
 * Props shared with every page (app/Http/Middleware/HandleInertiaRequests.php).
 */
export interface SharedProps {
  name: string;
  locale: string;
  auth: {
    user: {
      id: Id;
      name: string;
      firstname: string;
      lastname: string | null;
      email: string;
      avatar: string | null;
      is_superadmin: boolean;
      verified: boolean;
    } | null;
  };
  projects: { id: Id; name: string; is_admin: boolean }[];
  project: {
    id: Id;
    name: string;
    is_approved: boolean;
    approval_requested_at: string | null;
    family: { id: Id; name: string } | null;
    can: { manage: boolean; invite: boolean; rank: boolean; upload: boolean; approve: boolean };
  } | null;
  notifications: { unread: number } | null;
  flash: {
    success: string | null;
    info: string | null;
    warning: string | null;
    error: string | null;
  };
  errors: Record<string, string>;
  ziggy: Record<string, unknown> & { location: string };
  [key: string]: unknown;
}
