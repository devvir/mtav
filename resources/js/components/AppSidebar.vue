<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import useProjects from '@/store/useProjects';
import { type NavItem } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { BookOpen, Building2Icon, Folder, HomeIcon, LayoutGrid, UsersIcon } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const page = usePage();
const projectsStore = useProjects();

const mainNavItems: NavItem[] = [];

if (projectsStore.current) {
    mainNavItems.push(
        ...[
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
                href: route('users.index'),
                icon: UsersIcon,
            },
        ],
    );
}

if (page.props.auth.user.is_admin) {
    mainNavItems.push({
        title: trans('Projects'),
        href: route('projects.index'),
        icon: Building2Icon,
    });
}

const footerNavItems: NavItem[] = [
    {
        title: trans('Documentation'),
        href: 'https://laravel.com/docs/starter-kits#vue', // TODO : replace with actual documentation URL
        icon: BookOpen,
    },
];

if (page.props.auth.user.is_admin) {
    footerNavItems.push({
        title: trans('Github Repo'),
        href: 'https://github.com/laravel/vue-starter-kit', // TODO : replace with actual repository URL
        icon: Folder,
    });
}
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('home')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
