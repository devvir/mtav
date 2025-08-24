import { router, usePage } from "@inertiajs/vue3";
import { getCurrentProject } from "./useProjects";
import { trans } from "laravel-vue-i18n";
import { BookOpen, Building2Icon, Folder, HomeIcon, LayoutGrid, UsersIcon } from "lucide-vue-next";
import { computed, ComputedRef, Ref, ref } from "vue";
import { NavItem } from "@/types";
import { useRoute } from "ziggy-js";

interface NavItemBuilder extends NavItem {
    if?: ComputedRef<boolean>;
    routes?: string[];
    ref?: NavItem;
};

const page = usePage();
const route = useRoute();
const currentProject = getCurrentProject();

const projectIsSelected = computed(() => !! currentProject.value);
const userIsAdmin = computed(() => page.props.auth.user?.is_admin);
const userIsSuperadmin = computed(() => page.props.auth.user?.is_superadmin);

const mainItems: NavItemBuilder[] = [
    {
        title: trans('Dashboard'),
        href: route('home'),
        icon: HomeIcon,
        if: projectIsSelected,
        routes: ['home'],
    },
    {
        title: trans('Gallery'),
        href: route('gallery'),
        icon: LayoutGrid,
        if: projectIsSelected,
        routes: ['gallery'],
    },
    {
        title: trans('Members'),
        href: route('families.index'),
        icon: UsersIcon,
        if: computed(() => projectIsSelected.value || userIsSuperadmin.value),
        routes: ['families*', 'members*', 'users*', 'admins*'],
    },
    {
        title: trans('Projects'),
        href: route('projects.index'),
        icon: Building2Icon,
        if: userIsAdmin,
        routes: ['projects*'],
    },
];

const footerItems: NavItemBuilder[] = [
    {
        title: trans('Documentation'),
        href: 'https://laravel.com/docs/starter-kits#vue', // TODO : replace with actual documentation URL
        icon: BookOpen,
    },
    {
        title: trans('Github Repo'),
        href: 'https://github.com/laravel/vue-starter-kit', // TODO : replace with actual repository URL
        icon: Folder,
        if: userIsAdmin,
    },
];

const filterOutNavItems = (item: NavItemBuilder): boolean => {
    return ! item.if || item.if?.value;
}

const buildNavItem = (builder: NavItemBuilder): NavItem => {
    const { title, href, icon } = builder;

    return builder.ref = { title , href , icon, isActive: false };
}

const mainNavItems: ComputedRef<NavItem[]> = computed(
    () => mainItems.filter(filterOutNavItems).map(buildNavItem)
);

const footerNavItems: ComputedRef<NavItem[]> = computed(
    () => footerItems.filter(filterOutNavItems).map(buildNavItem)
);

const activeNavItem: Ref<NavItem | undefined> = ref();

const setActiveNavItem = () => {
    activeNavItem.value = mainItems.find(({ routes }) => {
        return !! routes
            ?.map(r => new RegExp(`^${r.replace('\*', '.*')}$`))
            .find(regex => regex.test(route().current() ?? ''));
    })?.ref;
};

router.on('navigate', setActiveNavItem);

export {
    mainNavItems,
    footerNavItems,
    activeNavItem,
};
