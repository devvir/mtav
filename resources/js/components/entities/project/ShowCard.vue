<script setup lang="ts">
import { BinaryBadge } from '@/components/badge';
import {
  Card,
  CardContent,
  CardFooter,
  CardHeader,
  ContentHighlight,
  ContentLine,
  CreatedMeta,
  FooterButton,
} from '@/components/card';
import { currentProject } from '@/composables/useProjects';
import { entityLabel } from '@/composables/useResources';
import { _ } from '@/composables/useTranslations';
import { Home, Users } from 'lucide-vue-next';

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
        <div class="rounded-lg border border-border bg-surface-elevated p-2 text-center">
          <div class="mb-1 flex items-center justify-center gap-2">
            <Users class="size-5 text-text-muted" />
            <div class="text-lg font-semibold text-text">
              {{ project.families_count }}
            </div>
          </div>
          <div class="text-xs text-text-muted">
            {{ entityLabel('family', project.families_count) }}
          </div>
        </div>

        <div class="rounded-lg border border-border bg-surface-elevated p-2 text-center">
          <div class="mb-1 flex items-center justify-center gap-2">
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

      <FooterButton :href="actionRoute" :method="isCurrentProject ? 'DELETE' : 'POST'">
        {{ isCurrentProject ? _('Selected') : _('Select') }}
      </FooterButton>
    </CardFooter>
  </Card>
</template>
