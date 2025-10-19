<script setup lang="ts">
import { resetCurrentProject, setCurrentProject } from '@/composables/useProjects';
import { _ } from '@/composables/useTranslations';

defineProps<{
  project: Project;
  selected: boolean;
}>();
</script>

<template>
  <button
    @click.stop.prevent="selected ? resetCurrentProject() : setCurrentProject(project)"
    class="inline-flex items-center space-x-2 border px-4 py-2 leading-wide transition-all duration-300 not-group-hover:opacity-90 group-hover:scale-105 hover:px-wide"
    :class="
      selected
        ? 'rounded-2xl border-current/10 bg-accent text-accent-foreground'
        : 'rounded border-current text-foreground/80 hover:rounded-2xl hover:border-0 hover:bg-accent/70 hover:text-accent-foreground'
    "
    :title="selected ? _('Click to deselect') : _('Click to set as current project')"
  >
    <span>{{ selected ? _('Selected') : _('Select') }}</span>

    <svg v-if="selected" class="size-4 fill-none stroke-current stroke-3" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" d="M4,14 l5,5 L20,5" />
    </svg>
  </button>
</template>
