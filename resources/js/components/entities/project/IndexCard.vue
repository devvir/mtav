<script setup lang="ts">
import { cn } from '@/lib/utils';
import { Card, CardHeader, CardContent, CardFooter, CreatedMeta, FooterButton } from '@/components/card';
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
  <Card
    :resource="project"
    entity="project"
    type="index"
    :class="cn($props.class, { 'opacity-60': ! project.active })"
  >
    <CardHeader :title="project.name">
      <BinaryBadge
        :when="project.active"
        :then="_('Active')"
        :else="_('Inactive')"
        :variants="['success', 'danger']"
      />
    </CardHeader>

    <CardContent>
      <div class="text-sm text-text-muted">{{ project.organization }}</div>

      <div class="flex items-center gap-4 text-sm text-text-muted">
        <div class="flex items-center gap-1">
          <Users class="h-4 w-4" />
          <span>{{ project.families_count }} {{ entityLabel('family', project.families_count) }}</span>
        </div>
        <div class="flex items-center gap-1">
          <Home class="h-4 w-4" />
          <span>{{ project.units_count }} {{ entityLabel('unit', project.units_count) }}</span>
        </div>
      </div>
    </CardContent>

    <CardFooter class="flex items-center justify-between text-xs">
      <CreatedMeta />

      <FooterButton :href="actionRoute" :method="isCurrentProject ? 'DELETE' : 'POST'">
        {{ isCurrentProject ? _('Selected') : _('Select') }}
      </FooterButton>
    </CardFooter>
  </Card>
</template>