/**
 * Shapes of the data pages receive, mirroring app/Http/Resources.
 */

export type Id = number;
export type Ref = { id: Id; name: string };

export interface Can {
  update?: boolean;
  delete?: boolean;
}

export interface Project {
  id: Id;
  name: string;
  description: string;
  organization: string | null;
  active: boolean;
  is_approved: boolean;
  approval_requested_at: string | null;
  created_at: string;
  members_count?: number;
  families_count?: number;
  units_count?: number;
  is_admin?: boolean;
}

export interface Person {
  id: Id;
  name: string;
  firstname: string;
  lastname: string | null;
  avatar: string | null;
  about: string | null;
  is_admin: boolean;
  family: Ref | null;
  joined_at: string | null;
  email?: string;
  phone?: string | null;
}

export interface Family {
  id: Id;
  name: string;
  avatar: string | null;
  unit_type?: Ref;
  unit?: { id: Id; identifier: string } | null;
  members?: Person[];
  members_count?: number;
  can: Can;
}

export interface UnitType {
  id: Id;
  name: string;
  description: string | null;
  units_count?: number;
  families_count?: number;
  units?: Unit[];
  families?: Family[];
  can: Can;
}

export interface Unit {
  id: Id;
  identifier: string;
  type?: Ref;
  family?: Ref | null;
  can: Can;
}

export type EventType = 'onsite' | 'online' | 'lottery';
export type EventStatus = 'upcoming' | 'ongoing' | 'completed';

export interface ProjectEvent {
  id: Id;
  type: EventType;
  title: string;
  description: string;
  location: string | null;
  start_date: string | null;
  end_date: string | null;
  status: EventStatus;
  is_published: boolean;
  allows_rsvp: boolean;
  creator?: string | null;
  attending?: boolean | null;
  attendees?: Ref[];
  can: Can & { rsvp?: boolean };
}

export type MediaCategory = 'image' | 'video' | 'audio' | 'document';

export interface Media {
  id: Id;
  category: MediaCategory;
  description: string;
  alt_text: string | null;
  mime_type: string;
  file_size: number;
  width: number | null;
  height: number | null;
  url: string;
  thumbnail: string | null;
  owner?: Ref | null;
  created_at: string;
  can: Can;
}

export interface Invitation {
  id: Id;
  email: string;
  name: string | null;
  is_admin: boolean;
  family?: Ref | null;
  inviter?: string | null;
  expires_at: string;
  created_at: string;
  can: Can;
}

export interface Notification {
  id: Id;
  type: string;
  title: string;
  message: string;
  url: string | null;
  read: boolean;
  created_at: string;
}

export type LotteryRunStatus = 'running' | 'completed' | 'failed' | 'invalidated';

export interface LotteryRun {
  id: Id;
  status: LotteryRunStatus;
  started_at: string;
  finished_at: string | null;
  started_by?: string | null;
  failure: string | null;
  invalidated_at: string | null;
  invalidated_by?: string | null;
  invalidation_reason: string | null;
}

export type Point = [number, number];

export interface PlanItem {
  id: Id;
  type: string;
  floor: number;
  polygon: Point[];
  name: string | null;
  metadata: Record<string, unknown> | null;
  unit: { id: Id; identifier: string; unit_type_id: Id; family: Ref | null } | null;
}

export interface Plan {
  id: Id;
  polygon: Point[];
  width: number;
  height: number;
  unit_system: 'meters' | 'feet';
  items: PlanItem[];
}

export interface Paginated<T> {
  data: T[];
  links: { first: string | null; last: string | null; prev: string | null; next: string | null };
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
  };
}
