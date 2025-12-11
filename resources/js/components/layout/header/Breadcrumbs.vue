<script setup lang="ts">
import { can } from '@/composables/useAuth';
import { currentProject } from '@/state/useCurrentProject';
import { _ } from '@/composables/useTranslations';
import Breadcrumb from './Breadcrumb.vue';

defineProps<{
  global?: boolean;
}>();
</script>

<template>
  <Teleport to="[data-slot='breadcrumbs']" defer>
    <ul
      class="group @container/breadcrumbs flex items-baseline gap-2 pr-8 text-sm opacity-90 transition-all not-last:hidden @max-sm:hidden *:not-last:not-hocus:opacity-70 *:not-first:before:mr-2 *:not-first:before:content-['›'] *:last:pointer-events-none @max-sm:*:not-last:hidden @max-sm:*:before:hidden @sm:*:last:shrink-0"
    >
      <Breadcrumb
        v-if="!global && currentProject && can.viewAny('projects')"
        route="projects.show"
        :params="currentProject.id"
        class="@max-lg:hidden @max-lg:[&+*]:before:hidden"
      >
        <span class="font-bold">{{ _('Project') }}</span>
        : {{ currentProject.name }}
      </Breadcrumb>

      <slot />
    </ul>
  </Teleport>
</template>
