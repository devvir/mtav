<script setup lang="ts">
import FlashMessagesOverlay from '@/components/flash/FlashMessagesOverlay.vue';
import BreadcrumbsContainer from '@/components/layout/header/BreadcrumbsContainer.vue';
import { SidebarTrigger } from '@/components/layout/sidebar';
import Notifications from '@/components/Notifications.vue';
import QuickActions from '@/components/QuickActions.vue';
import { useBroadcasting } from '@/composables/useBroadcasting';

// TEMPORARY: Test broadcasting system
const { onMessage, onPrivateMessage, onProjectMessage, onAnyMessage } = useBroadcasting();

// Listen for navigation messages
onMessage('user.navigation', (message) => {
  console.log('[AppSidebarHeader] Navigation message received:', message);
});

// Listen for any private channel message
onPrivateMessage((message) => {
  console.log('[AppSidebarHeader] Private channel message:', message);
});

// Listen for project messages
onProjectMessage((message, projectId) => {
  console.log('[AppSidebarHeader] Project message:', message, 'Project ID:', projectId);
});

// Listen to everything
onAnyMessage((message) => {
  console.log('[AppSidebarHeader] ANY message received:', message);
});
</script>

<template>
  <header class="@container/header m-0 mb-base @md:px-base">
    <div
      class="relative z-20 flex items-center justify-between gap-base border-t border-r border-border bg-linear-to-r from-sidebar/70 to-background to-70% px-4 xs:px-8 py-4 shadow-lg shadow-foreground/10 transition-[width,height] ease-linear @md:mt-4 mb-4 @md:mb-wide @md:rounded-lg @md:px-wide @md:py-3"
    >
      <div class="flex flex-1 items-center gap-4">
        <SidebarTrigger />

        <BreadcrumbsContainer />
      </div>

      <div class="flex items-center gap-4">
        <Notifications />
        <QuickActions />
      </div>

      <!-- Flash messages overlay -->
      <FlashMessagesOverlay />
    </div>

    <div data-slot="header-after" class="z-10" />
  </header>
</template>
