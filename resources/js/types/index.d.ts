interface JsonResource {
  id: number;
  created_at: string;
  allows: {
    viewAny: boolean;
    view: boolean;
    create: boolean;
    update: boolean;
    delete: boolean;
    restore: boolean;
    forceDelete: boolean;
  };
}

interface PaginationSpec {
  path: string;
  current_page: number;
  last_page: number;
  next_page_url: number | null;
  total: number;
}

interface PaginatedResources extends PaginationSpec {
  data: JsonResource[];
}

interface User extends JsonResource {
  id: number;
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

interface Family extends JsonResource {
  id: number;
  name: string;

  project: Project & { loaded?: boolean };
  members?: User[];
}

interface Project extends JsonResource {
  id: number;
  name: string;
  status: boolean;

  admins?: User[];
  admins_count?: number;
  members?: User[];
  members_count?: number;
  families?: Family[];
  families_count?: number;
}

interface PaginatedUsers extends PaginatedResources {
  data: User[];
}

interface PaginatedFamilies extends PaginatedResources {
  data: Family[];
}

interface PaginatedProjects extends PaginatedResources {
  data: Project[];
}

interface NavItem {
  label: string;
  route: string;
  icon: any;
  onlyIf?: ComputedRef<boolean>;
  routes?: string[];
}
