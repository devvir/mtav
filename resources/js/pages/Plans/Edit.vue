<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import EditorCanvas from '@/components/projectplan/editor/EditorCanvas.vue';
import { _ } from '@/composables/useTranslations';

defineProps<{
  plan: ApiResource<Plan>;
  project: ApiResource<Project>;
}>();

/**
 * Check if running on desktop (viewport >= 1024px AND mouse pointer available)
 */
const width = window.innerWidth >= 1024;
const hasMouse = window.matchMedia('(pointer:fine)').matches;
const isDesktop = width && hasMouse;
</script>

<template>
  <Head :title="`${_('Project Plan View')} - ${_('Edit')}`" />

  <Breadcrumbs>
    <Breadcrumb text="Plan" route="plans.edit" :params="{ plan: plan.id }" />
  </Breadcrumbs>

  <!-- Desktop: Editor -->
  <div v-if="isDesktop" class="flex flex-col">
    <EditorCanvas :plan />
  </div>

  <!-- Mobile/Tablet: Notice -->
  <div v-else class="flex items-center justify-center h-96 bg-background">
    <div class="text-center">
      <svg class="mx-auto h-12 w-12 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
      </svg>

      <p class="mt-4 text-lg font-semibold text-foreground">{{ _('Desktop required') }}</p>
      <p class="mt-2 text-muted-foreground">{{ _('Plan editing is only available on desktop with a mouse') }}</p>

      <Link :href="route('lottery')"
        class="mt-6 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-background bg-primary hover:bg-primary/90"
      >{{ _('Back') }}</Link>
    </div>
  </div>
</template>
