<script setup lang="ts">
import { BinaryBadge } from '@/components/badge';
import {
  EntityCard,
  CardContent,
  CardFooter,
  CardHeader,
  ContentGrid,
  ContentHighlight,
  ContentLine,
  CreatedMeta,
  FooterButton,
  StatBox,
} from '@/components/card';
import { currentProject } from '@/composables/useProjects';
import { _ } from '@/composables/useTranslations';
import { Calendar, Camera, Home, Shield, User, UsersRound } from 'lucide-vue-next';

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
  <EntityCard :resource="project" entity="project" type="show">
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

      <!-- Statistics -->
      <ContentGrid>
        <StatBox
          :icon="UsersRound"
          :count="project.families_count"
          entity="family"
          icon-color="text-blue-500"
          route="families.index"
        />

        <StatBox
          :icon="User"
          :count="project.members_count"
          entity="member"
          icon-color="text-green-500"
          route="members.index"
        />

        <StatBox
          :icon="Shield"
          :count="project.admins_count"
          entity="admin"
          icon-color="text-purple-500"
          route="admins.index"
        />
      </ContentGrid>

      <ContentGrid>
        <StatBox
          :icon="Home"
          :count="project.units_count"
          entity="unit"
          icon-color="text-orange-500"
          route="units.index"
        />

        <StatBox
          v-if="project.events_count !== undefined"
          :icon="Calendar"
          :count="project.events_count"
          entity="event"
          icon-color="text-indigo-500"
          route="events.index"
        />

        <StatBox
          v-if="project.media_count !== undefined"
          :icon="Camera"
          :count="project.media_count"
          entity="media"
          icon-color="text-pink-500"
          route="media.index"
        />
      </ContentGrid>

      <!-- Description -->
      <ContentHighlight class="min-h-32">
        {{ project.description }}
      </ContentHighlight>
    </CardContent>

    <CardFooter class="flex items-center justify-between">
      <CreatedMeta />

      <FooterButton :href="actionRoute" :method="isCurrentProject ? 'DELETE' : 'POST'">
        {{ isCurrentProject ? _('Selected') : _('Select') }}
      </FooterButton>
    </CardFooter>
  </EntityCard>
</template>
