type AppEntity = 'project' | 'unit' | 'unit_type' | 'admin' | 'family' | 'member' | 'event' | 'media' | 'log';

type AppEntityPluralForm =
  | 'projects'
  | 'units'
  | 'unit_types'
  | 'admins'
  | 'families'
  | 'members'
  | 'events'
  | 'media'
  | 'logs';

type AppEntityNS = AppEntityPluralForm;

type ResourceAction = 'index' | 'show' | 'create' | 'edit' | 'destroy' | 'restore';
type ResourcePolicy = 'view' | 'update' | 'delete' | 'restore' | 'forceDelete';

interface Resource {
  id: number;
  created_at: string;
  created_ago: string;
  deleted_at: string | null;
}

interface Subject {
  name: string;
  avatar: string;
}

interface Project extends Resource {
  name: string;
  description: string;
  organization: string;
  active: boolean;

  admins?: ApiResource<Admin>[];
  admins_count?: number;
  members?: ApiResource<Member>[];
  members_count?: number;
  families?: ApiResource<Family>[];
  families_count?: number;
  unit_types?: ApiResource<UnitType>[];
  unit_types_count?: number;
  units?: ApiResource<Unit>[];
  units_count?: number;
  media?: ApiResource<Media>[];
  media_count?: number;
  events?: ApiResource<Event>[];
  events_count?: number;
  log?: ApiResource<Log>[];
  log_count?: number;
}

interface User extends Resource, Subject {
  email: string;
  phone: string;
  firstname: string;
  lastname: string;
  about: string | null;
  is_admin: boolean;

  projects?: ApiResource<Project>[];

  // Sensitive data (only present for admins)
  legal_id?: string;
  is_verified?: boolean;
  is_superadmin?: boolean;
  email_verified_at?: string;
  invitation_accepted_at?: string;
}

interface Member extends User {
  family: { id: number } | ApiResource<Family>;
  project?: ApiResource<Project> | null;
}

interface Admin extends User {
  manages?: ApiResource<Project>[];
}

interface Family extends Resource, Subject {
  unit_type: { id: number } | ApiResource<UnitType>;
  project: { id: number } | ApiResource<Project>;
  members?: ApiResource<User>[];
  members_count?: number;
}

interface UnitType extends Resource {
  name: string;
  description: string;

  project: { id: number } | ApiResource<Project>;
  families?: ApiResource<Family>[];
  families_count?: number;
  units?: ApiResource<Unit>[];
  units_count?: number;
}

interface Unit extends Resource {
  identifier: string | null;

  project: ApiResource<Project> | { id: number };
  type: ApiResource<UnitType> | { id: number };
  family: ApiResource<Family> | { id: number } | null;
}

interface Media extends Resource {
  filename: string;
  description: string | null;
  url: string;
  thumbnail_url: string | null;
}

type EventType = 'lottery' | 'online' | 'onsite';
type EventTypes = Record<EventType, string>;

interface Event extends Resource {
  type: EventType;
  title: string;
  description: string;
  start_date: string | null;
  end_date: string | null;
  location: string | null;
  is_published: boolean;
  is_lottery: boolean;
  is_online: boolean;
  is_onsite: boolean;
  type_label: string;

  creator: ApiResource<Admin> | { id: number };
  project: ApiResource<Project> | { id: number };
}

interface Log extends Resource {
  event: string;
  creator: string;
  creator_href: string | null;

  user: { id: number | null } | null | ApiResource<User>;
  project: { id: number | null } | null | ApiResource<Project>;
}

interface ApiResource<R extends Resource = Resource> extends R {
  allows: Record<ResourcePolicy, boolean>;
}

interface ApiResources<R extends Resource = Resource> {
  data: ApiResource<R>[];

  path: string;
  total: number;
  last_page: number;
  current_page: number;
  next_page_url: number | null;
}

type ApiDataPage = Exclude<ApiResources, 'data'> & { data: unknown[] };
