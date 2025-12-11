<script setup lang="ts">
import { CardFooter, CreatedMeta, FooterButton } from '@/components/card';
import { currentProject } from '@/state/useCurrentProject';
import { _ } from '@/composables/useTranslations';

const props = defineProps<{
  project: ApiResource<Project>;
}>();

const isCurrentProject = computed(() => currentProject.value?.id === props.project.id);

const actionRoute = computed(() =>
  isCurrentProject.value
    ? route('resetCurrentProject')
    : route('setCurrentProject', props.project.id),
);
</script>

<template>
    <CardFooter class="flex items-center justify-between text-xs">
      <CreatedMeta />

      <FooterButton
        :href="actionRoute"
        :variant="isCurrentProject ? '' : 'outline'"
        :method="isCurrentProject ? 'DELETE' : 'POST'"
        :title="isCurrentProject ? _('Back to global workspace') : _('Set as your current Project')"
        :class="{ 'group-hocus:bg-background! group-hocus:text-text-subtle transition-all duration-300': isCurrentProject }"
        @click="router.flushAll()"
      >
        {{ isCurrentProject ? _('Selected') : _('Select') }}
      </FooterButton>
    </CardFooter>
</template>
