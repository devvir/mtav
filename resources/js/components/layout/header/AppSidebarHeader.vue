<script setup lang="ts">
import FlashMessagesOverlay from '@/components/flash/FlashMessagesOverlay.vue';
import BreadcrumbsContainer from '@/components/layout/header/BreadcrumbsContainer.vue';
import { SidebarTrigger } from '@/components/layout/sidebar';
import QuickActions from '@/components/QuickActions.vue';
</script>

<template>
  <header class="@container/header m-0 mb-base @md:px-base">
    <div
      class="relative flex items-center justify-between gap-base border-t border-r border-border bg-gradient-to-r from-sidebar/70 to-background to-70% px-8 py-4 shadow-lg shadow-foreground/10 transition-[width,height] ease-linear @md:mt-4 @md:mb-wide @md:rounded-lg @md:px-wide @md:py-3 z-20"
    >
      <div class="flex flex-1 items-center gap-4">
        <SidebarTrigger />

        <BreadcrumbsContainer />
      </div>

      <QuickActions />

      <!-- Flash messages overlay -->
      <FlashMessagesOverlay />
    </div>

    <div data-slot="header-after" class="z-10" />
  </header>
</template>
