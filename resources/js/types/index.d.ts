interface Resource {
  id: number;
  created_at: string;
}

interface User extends Resource {
  email: string;
  phone: string;
  name: string;
  firstname: string;
  lastname: string;
  avatar: string;
  is_admin: boolean;
  is_superadmin: boolean;
  created_ago: string;

  family: Family & { loaded?: boolean };
  project?: Project & { loaded?: boolean };
  projects?: Project[] & { loaded?: boolean };
}

interface Admin extends User {
  family: { id: null };
}

interface Family extends Resource {
  name: string;
  avatar: string;

  project: Project & { loaded?: boolean };
  members?: User[];
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

interface ApiResource<R extends Resource = Resource> extends R {
  allows: Record<Policy, boolean>;
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

type Policy = 'viewAny' | 'view' | 'create' | 'update' | 'delete' | 'restore' | 'forceDelete';
