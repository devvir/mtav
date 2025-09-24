<script setup lang="ts">
import UserInfo from '@/components/UserInfo.vue';
import { SidebarMenu, SidebarMenuButton, SidebarMenuItem, useSidebar } from '@/components/layout/sidebar';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { ChevronsUpDown } from 'lucide-vue-next';
import UserMenuContent from './UserMenuContent.vue';

const page = usePage();
const user = page.props.auth.user as User;
const { isMobile, state } = useSidebar();
</script>

<template>
  <SidebarMenu>
    <SidebarMenuItem>
      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <SidebarMenuButton size="lg" class="data-[state=open]:bg-accent data-[state=open]:text-accent-foreground">
            <UserInfo :user="user" />
            <ChevronsUpDown class="ml-0 size-4" />
          </SidebarMenuButton>
        </DropdownMenuTrigger>
        <DropdownMenuContent
          class="w-(--reka-dropdown-menu-trigger-width) min-w-56 rounded-lg"
          :side="isMobile ? 'bottom' : state === 'collapsed' ? 'left' : 'bottom'"
          align="end"
          :side-offset="4"
        >
          <UserMenuContent :user="user" />
        </DropdownMenuContent>
      </DropdownMenu>
    </SidebarMenuItem>
  </SidebarMenu>
</template>
