<script setup lang="ts">
import AppContent from '@/components/layout/AppContent.vue';
import AppShell from '@/components/layout/AppShell.vue';
import AppSidebarHeader from '@/components/layout/header/AppSidebarHeader.vue';
import AppSidebar from '@/components/layout/sidebar/AppSidebar.vue';
</script>

<template>
  <AppShell variant="sidebar">
    <AppSidebar />
    <AppContent variant="sidebar">
      <AppSidebarHeader
        class="@container/header sticky inset-0 z-2 bg-background/40 backdrop-blur-lg"
      />

      <div class="@container/main relative z-1 h-full px-padding-x pb-padding-y">
        <slot />
      </div>
    </AppContent>
  </AppShell>
</template>
