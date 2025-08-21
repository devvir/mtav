import { usePage } from "@inertiajs/vue3";
import { getCurrentProject } from "./useProjects";
import { trans } from "laravel-vue-i18n";
import { BookOpen, Building2Icon, Folder, HomeIcon, LayoutGrid, UsersIcon } from "lucide-vue-next";
import { computed, ComputedRef } from "vue";
import { NavItem } from "@/types";
import { listMembersByFamily } from "./useMemberGrouping";

const page = usePage();
const currentProject = getCurrentProject();

const projectMainNavItems = [
    {
        title: trans('Dashboard'),
        href: route('home'),
        icon: HomeIcon,
    },
    {
        title: trans('Gallery'),
        href: route('gallery'),
        icon: LayoutGrid,
    },
    {
        title: trans('Members'),
        href: listMembersByFamily().value ? route('families.index') : route('users.index'),
        icon: UsersIcon,
    },
];

const adminMainNavItems = [
    {
        title: trans('Projects'),
        href: route('projects.index'),
        icon: Building2Icon,
    },
];

const mainNavItems: ComputedRef<NavItem[]> = computed(() => [
    ...(currentProject.value ? projectMainNavItems : []),
    ...(page.props.auth.user.is_admin ? adminMainNavItems : []),
]);

const openFooterNavItems = [
    {
        title: trans('Documentation'),
        href: 'https://laravel.com/docs/starter-kits#vue', // TODO : replace with actual documentation URL
        icon: BookOpen,
    }
];

const adminFooterNavItems = [
    {
        title: trans('Github Repo'),
        href: 'https://github.com/laravel/vue-starter-kit', // TODO : replace with actual repository URL
        icon: Folder,
    },
];

const footerNavItems: ComputedRef<NavItem[]> = computed(() => [
    ...openFooterNavItems,
    ...(page.props.auth.user.is_admin ? adminFooterNavItems : []),
]);

export {
    mainNavItems,
    footerNavItems,
};
