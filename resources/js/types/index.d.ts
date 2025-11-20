type AppEntity =
  | 'project'
  | 'unit'
  | 'unit_type'
  | 'admin'
  | 'family'
  | 'member'
  | 'event'
  | 'media'
  | 'log';

type AppEntityNS =
  | 'projects'
  | 'units'
  | 'unit_types'
  | 'admins'
  | 'families'
  | 'members'
  | 'events'
  | 'media'
  | 'logs';

type AppEntityPluralForm = Exclude<AppEntityNS, 'media'> | 'files';

type ResourceAction = 'index' | 'show' | 'create' | 'edit' | 'destroy' | 'restore';
type ResourcePolicy = 'view' | 'update' | 'delete' | 'restore' | 'forceDelete';

type MediaCategory = 'audio' | 'document' | 'image' | 'unknown' | 'video' | 'visual';

interface Lottery extends Event {
  type: 'lottery';
  end_date: null;
  end_date_raw: null;
  allows_rsvp: false;
  is_lottery: true;
  is_online: false;
  is_onsite: false;
  is_published: true;
  creator: null;
}

// WithRsvp type aliases
type MemberWithRsvps = Member & Required<MemberRsvpFields>;
type EventWithRsvps = Event & Required<EventRsvpFields>;

// WithMedia type aliases
type ProjectWithMedia = Project & Required<HasMedia>;
type AdminWithMedia = Admin & Required<HasMedia>;
type MemberWithMedia = Member & Required<HasMedia>;
type FamilyWithMedia = Family & Required<HasMedia>;

// Plan-specific types
type PlanPolygon = number[];
type PlanUnitSystem = 'meters' | 'feet';
type PlanItemMetadata = Record<string, any>;

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

type EventType = 'lottery' | 'online' | 'onsite';
type EventTypes = Record<EventType, string>;

interface HasEvents {
  events?: ApiResource<Event>[];
  upcoming_events?: ApiResource<Event>[];

  events_count?: number;
  upcoming_events_count?: number;
}

interface HasMedia {
  media?: ApiResource<Media>[];
  images?: ApiResource<Media>[];
  videos?: ApiResource<Media>[];

  media_count?: number;
  images_count?: number;
  videos_count?: number;
}

interface Project extends Resource, HasEvents, HasMedia {
  name: string;
  description: string;
  organization: string;
  active: boolean;

  plan?: ApiResource<Plan>;
  admins?: ApiResource<Admin>[];
  members?: ApiResource<Member>[];
  families?: ApiResource<Family>[];
  unit_types?: ApiResource<UnitType>[];
  units?: ApiResource<Unit>[];
  log?: ApiResource<Log>[];

  admins_count?: number;
  members_count?: number;
  families_count?: number;
  unit_types_count?: number;
  units_count?: number;
  log_count?: number;
}

interface Plan extends Resource {
  polygon: PlanPolygon;
  width: number;
  height: number;
  unit_system: PlanUnitSystem;

  project: { id: number } | ApiResource<Project>;
  items: ApiResource<PlanItem>[];

  items_count: number;
}

interface PlanItem extends Resource {
  type: string;
  polygon: PlanPolygon;
  floor: number;
  name: string | null;
  metadata: PlanItemMetadata | null;

  plan: { id: number } | ApiResource<Plan>;
  unit?: ApiResource<Unit> | null;
}

interface User extends Resource, Subject, HasEvents, HasMedia {
  email: string;
  phone: string;
  firstname: string;
  lastname: string;
  about: string | null;
  is_admin: boolean;

  projects?: ApiResource<Project>[];

  projects_count?: number;

  // Sensitive data (only present for admins)
  legal_id?: string;
  is_verified?: boolean;
  is_superadmin?: boolean;
  email_verified_at?: string;
  invitation_accepted_at?: string;
}

interface MemberRsvpFields {
  rsvps: ApiResource<Event>[];
  upcoming_rsvps: ApiResource<Event>[];
  acknowledged_events: ApiResource<Event>[];
  accepted_events: ApiResource<Event>[];
  declined_events: ApiResource<Event>[];

  rsvps_count: number;
  upcoming_rsvps_count: number;
  acknowledged_events_count: number;
  accepted_events_count: number;
  declined_events_count: number;
}

interface Member extends User, Partial<MemberRsvpFields> {
  family: { id: number } | ApiResource<Family>;
  project?: ApiResource<Project> | null;
}

interface Admin extends User {
  manages?: ApiResource<Project>[];

  manages_count?: number;
}

interface Family extends Resource, Subject, HasMedia {
  unit_type: { id: number } | ApiResource<UnitType>;
  project: { id: number } | ApiResource<Project>;
  members?: ApiResource<User>[];
  preferences?: ApiResource<Unit>[];

  members_count?: number;
  preferences_count?: number;
}

interface UnitType extends Resource {
  name: string;
  description: string;

  project: { id: number } | ApiResource<Project>;
  families?: ApiResource<Family>[];
  units?: ApiResource<Unit>[];

  families_count?: number;
  units_count?: number;
}

interface Unit extends Resource {
  identifier: string | null;

  type: ApiResource<UnitType> | { id: number };
  project: ApiResource<Project> | { id: number };
  family: ApiResource<Family> | { id: number | null } | null;
  plan?: ApiResource<Plan>;
  plan_item?: ApiResource<PlanItem>;
}

interface Media extends Resource {
  path: string;
  url: string;
  description: string;
  alt_text: string | null;
  width: number | null;
  height: number | null;
  category: string;
  category_label: string;
  mime_type: string;
  file_size: number;
  is_image: boolean;

  owner: { id: number } | ApiResource<User>;
  project: { id: number } | ApiResource<Project>;
}

interface EventRsvpFields {
  rsvps: ApiResource<User>[];
  accepted: boolean;
  declined: boolean;

  rsvps_count: number;
  accepted_count: number;
  declined_count: number;
}

interface Event extends Resource, Partial<EventRsvpFields> {
  type: EventType;
  type_label: string;
  status: string;
  title: string;
  description: string;
  start_date: string | null;
  end_date: string | null;
  location: string | null;
  is_published: boolean;
  allows_rsvp: boolean;
  is_lottery: boolean;
  is_online: boolean;
  is_onsite: boolean;

  // yyyy-MM-ddThh:mm
  start_date_raw: string | null;
  end_date_raw: string | null;

  project: ApiResource<Project> | { id: number };
  creator: ApiResource<Admin> | { id: number | null } | null;
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
