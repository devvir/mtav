type AppResource = 'projects' | 'units' | 'unit-types' | 'admins' | 'families' | 'members' | 'logs';
type ResourcePolicy = 'view' | 'update' | 'delete' | 'restore' | 'forceDelete';

interface Resource {
  id: number;
  created_at: string;
  created_ago: string;
  deleted_at: string | null;
}

interface Project extends Resource {
  name: string;
  description: string;
  organization: string;
  active: boolean;

  admins?: User[];
  admins_count?: number;
  members?: User[];
  members_count?: number;
  families?: Family[];
  families_count?: number;
}

interface User extends Resource {
  email: string;
  phone: string;
  name: string;
  firstname: string;
  lastname: string;
  bio: string | null;
  avatar: string;
  is_admin: boolean;

  // Sensitive data (only present for admins)
  legal_id?: string;
  is_verified?: boolean;
  is_superadmin?: boolean;
  email_verified_at?: string;
  invitation_accepted_at?: string;
}

interface Member extends User {
  family: Family & { loaded?: boolean };
  project?: Project & { loaded?: boolean };
}

interface Admin extends User {
  projects?: Project[] & { loaded?: boolean };
}

interface Family extends Resource {
  name: string;
  avatar: string;
  unit_type?: UnitType & { loaded?: boolean };
  project: Project & { loaded?: boolean };
  members?: User[];
  members_count?: number;
}

interface UnitType extends Resource {
  name: string;
  description: string;

  units_count?: number;
  families_count?: number;
}

interface Unit extends Resource {
  name: string;
  number?: string;
  project: Project & { loaded?: boolean };
  type?: UnitType & { loaded?: boolean };
  family?: Family | null;
}

interface Log extends Resource {
  project_id: number;
  event: string;
  creator: string;
  creator_href: string | null;

  user: User & { id: int | null };
  project: Project & { id: int | null };
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
