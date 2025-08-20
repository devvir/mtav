import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
    verified: boolean;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
};

export interface JsonResource {
    allows?: {
        viewAny: boolean;
        view: boolean;
        create: boolean;
        update: boolean;
        delete: boolean;
        restore: boolean;
        forceDelete: boolean;
    }
};

export interface User extends JsonResource {
    id: number;
    email: string;
    name: string;
    firstname: string;
    lastname: string | null;
    is_admin: boolean;
    is_superadmin: boolean;
    phone?: string | null;
    avatar?: string | null;
    created_at?: string;
    family?: Family;
};

export interface Family extends JsonResource {
    id: number;
    name: string;
    members?: User[];
    created_at: string;
};

export interface Project extends JsonResource {
    id: number;
    name: string;
    status: boolean;
    created_at: string;
};

export interface PaginatedResources {
    data: User[] | Family[] | Project[];

    path: string;
    current_page: number;
    last_page: number;
    next_page_url: number | null;
}

type PaginatedUsers = PaginatedResources & { data: User[] };
type PaginatedFamilies = PaginatedResources & { data: Family[] };
type PaginatedProjects = PaginatedResources & { data: Project[] };

export type BreadcrumbItemType = BreadcrumbItem;
