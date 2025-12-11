<script setup lang="ts">
import { EntityCard, CardContent, CardHeader } from '@/components/card';
import { _ } from '@/composables/useTranslations';
import { User, UsersRound } from 'lucide-vue-next';
import ProjectFooter from './shared/ProjectFooter.vue';

defineProps<{
  project: ApiResource<Project>;
}>();
</script>

<template>
  <EntityCard
    :resource="project"
    entity="project"
    type="index"
    class="group"
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

    <ProjectFooter :project />
  </EntityCard>
</template>
