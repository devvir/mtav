import type { Id } from './models';

/** The websocket server, as browsers reach it */
export interface RealtimeServer {
  key: string;
  host: string;
  port: number;
  scheme: 'http' | 'https';
}

/**
 * Props shared with every page (app/Http/Middleware/HandleInertiaRequests.php).
 */
export interface SharedProps {
  name: string;
  /** The institution behind this deployment, under the brand ("FING ≒ UDELAR") */
  institution: string | null;
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
      /** A superadmin listed as a deployer */
      can_deploy: boolean;
      verified: boolean;
    } | null;
  };
  projects: { id: Id; name: string; is_admin: boolean }[];
  lastProject: Id | null;
  project: {
    id: Id;
    name: string;
    is_approved: boolean;
    approval_requested_at: string | null;
    family: { id: Id; name: string } | null;
    /** For its admins: the invitations nobody has answered yet */
    pending_invitations: number | null;
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
  /** The route list, sent once and remembered */
  routes: Record<string, unknown>;
  /** Where browsers reach the websocket server; null when there are no live updates */
  realtime: RealtimeServer | null;
  ziggy: {
    defaults: Record<string, unknown>;
    location: { host: string; pathname: string; search: string };
    /** The current route's name */
    name: string | null;
  };
  [key: string]: unknown;
}
