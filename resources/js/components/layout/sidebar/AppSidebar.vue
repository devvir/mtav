<script setup lang="ts">
import AppLogo from '@/components/layout/AppLogo.vue';
import {
  NavFooter,
  NavMain,
  NavUser,
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuItem,
} from '.';
</script>

<template>
  <Sidebar collapsible="icon" variant="inset" class="px-1 text-sidebar-foreground/70">
    <SidebarHeader class="py-base text-accent-foreground">
      <SidebarMenu>
        <SidebarMenuItem class="px-4">
          <AppLogo />
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarHeader>

    <SidebarContent class="px-2 py-base">
      <NavMain />
    </SidebarContent>

    <SidebarFooter class="px-2 py-base">
      <NavFooter class="mb-3" />
      <NavUser />
    </SidebarFooter>
  </Sidebar>

  <slot />
</template>
