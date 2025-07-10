<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Folder, HomeIcon, LayoutGrid, UsersIcon } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { trans } from 'laravel-vue-i18n';

const mainNavItems: NavItem[] = [
    {
        title: trans('Dashboard'),
        href: '/dashboard',
        icon: HomeIcon,
    },
    {
        title: trans('Gallery'),
        href: '/gallery',
        icon: LayoutGrid,
    },
    {
        title: trans('Participants'),
        href: '/participants',
        icon: UsersIcon,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: trans('Documentation'),
        href: 'https://laravel.com/docs/starter-kits#vue', // TODO : replace with actual documentation URL
        icon: BookOpen,
    },
    {
        title: trans('Github Repo'),
        href: 'https://github.com/laravel/vue-starter-kit', // TODO : replace with actual repository URL
        icon: Folder,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
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
