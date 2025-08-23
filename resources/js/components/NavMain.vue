<script setup lang="ts">
import { SidebarGroup, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { activeNavItem } from '@/composables/useNavItems';
import { type NavItem } from '@/types';

defineProps<{
    items: NavItem[];
}>();
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton as-child :is-active="item.title == activeNavItem?.title" :tooltip="item.title">
                    <Link :href="item.href" prefetch>
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
