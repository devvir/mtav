<script setup lang="ts">
import {
  EntityCard,
  CardContent,
  CardFooter,
  CardHeader,
  CreatedMeta,
  FooterButton,
} from '@/components/card';
import { currentProject } from '@/composables/useProjects';
import { _ } from '@/composables/useTranslations';
import { User, UsersRound } from 'lucide-vue-next';

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
  <EntityCard
    :resource="project"
    entity="project"
    type="index"
    :dimmed="!project.active"
  >
    <CardHeader :title="project.name" :kicker="_('Project')" />

    <CardContent>
      <div v-if="project.organization" class="text-sm text-text-muted mb-3">
        {{ project.organization }}
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-2 gap-2 text-sm">
        <!-- Families -->
        <div class="flex items-center gap-2 text-text-muted">
          <UsersRound class="h-4 w-4 text-amber-600" />
          <span class="font-medium">{{ project.families_count }}</span>
          <span>{{ project.families_count === 1 ? _('Family') : _('Families') }}</span>
        </div>

        <!-- Members -->
        <div class="flex items-center gap-2 text-text-muted">
          <User class="h-4 w-4 text-cyan-600" />
          <span class="font-medium">{{ project.members_count }}</span>
          <span>{{ project.members_count === 1 ? _('Member') : _('Members') }}</span>
        </div>
      </div>
    </CardContent>

    <CardFooter class="flex items-center justify-between text-xs">
      <CreatedMeta />

      <FooterButton :href="actionRoute" :method="isCurrentProject ? 'DELETE' : 'POST'">
        {{ isCurrentProject ? _('Selected') : _('Select') }}
      </FooterButton>
    </CardFooter>
  </EntityCard>
</template>
