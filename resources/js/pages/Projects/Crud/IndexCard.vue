<script setup lang="ts">
import Card from '@/components/shared/Card.vue';
import { currentProject } from '@/composables/useProjects';
import { _ } from '@/composables/useTranslations';
import { ModalLink, useModal } from '@inertiaui/modal-vue';
import SelectDeselect from './SelectDeselect.vue';

defineProps<{
  project: Required<Project>;
}>();
</script>

<template>
  <Card
    class="group mx-auto h-full max-w-2xl"
    :class="currentProject?.id === project.id ? 'border border-accent-foreground shadow-none' : ''"
  >
    <template v-slot:header>
      <ModalLink
        :href="route('projects.show', project.id)"
        class="block w-full cursor-pointer text-right"
        :class="{ 'pointer-events-none': useModal() }"
      >
        <p class="truncate text-lg" :title="project.name">
          {{ project.name }}
        </p>
        <p class="text-md leading-wide text-muted-foreground/60 @md:text-base @xl:text-sm">
          {{ project.status ? _('Active') : _('Inactive') }}
        </p>
      </ModalLink>
    </template>

    <ModalLink :href="route('projects.show', project.id)" :class="{ 'pointer-events-none': useModal() }">
      <div class="my-wide-y border-b border-foreground/10 pb-wide-y">TODO : main project stats</div>
    </ModalLink>

    <div class="text-center">
      <SelectDeselect :project="project" :selected="currentProject?.id === project.id" />
    </div>
  </Card>
</template>
