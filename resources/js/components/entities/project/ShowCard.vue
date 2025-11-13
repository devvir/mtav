<script setup lang="ts">
import { Card, CardHeader, CardContent, CardFooter, ContentHighlight, ContentLine, CreatedMeta, FooterButton } from '@/components/card';
import { BinaryBadge } from '@/components/badge';
import { Users, Home } from 'lucide-vue-next';
import { _ } from '@/composables/useTranslations';
import { currentProject } from '@/composables/useProjects';
import { entityLabel } from '@/composables/useResources';

const props = defineProps<{
  project: ApiResource<Project>;
}>();

const isCurrentProject = computed(() => currentProject.value?.id === props.project.id);
const actionRoute = computed(() => isCurrentProject.value
    ? route('resetCurrentProject')
    : route('setCurrentProject', props.project.id));
</script>

<template>
  <Card :resource="project" entity="project" type="show">
    <CardHeader :title="project.name" :kicker="_('Project')">
      <BinaryBadge
        :when="project.active"
        :then="_('Active')"
        :else="_('Inactive')"
        :variants="['success', 'danger']"
      />
    </CardHeader>

    <CardContent>
      <!-- Organization -->
      <ContentLine :label="_('Organization')" :value="project.organization" />

      <!-- Description -->
      <ContentHighlight>{{ project.description }}</ContentHighlight>

      <!-- Statistics -->
      <div class="grid grid-cols-2 gap-3">
        <div class="text-center bg-surface-elevated border border-border rounded-lg p-2">
          <div class="flex items-center justify-center gap-2 mb-1">
            <Users class="size-5 text-text-muted" />
            <div class="text-lg font-semibold text-text">
              {{ project.families_count }}
            </div>
          </div>
          <div class="text-xs text-text-muted">
            {{ entityLabel('family', project.families_count) }}
         </div>
        </div>

        <div class="text-center bg-surface-elevated border border-border rounded-lg p-2">
          <div class="flex items-center justify-center gap-2 mb-1">
            <Home class="size-5 text-text-muted" />
            <div class="text-lg font-semibold text-text">
              {{ project.units_count }}
            </div>
          </div>
          <div class="text-xs text-text-muted">
            {{ entityLabel('unit', project.units_count) }}
          </div>
        </div>
      </div>
    </CardContent>

    <CardFooter class="flex items-center justify-between">
      <CreatedMeta />

      <FooterButton :href="actionRoute">
        {{ isCurrentProject ? _('Selected') : _('Select') }}
      </FooterButton>
    </CardFooter>
  </Card>
</template>