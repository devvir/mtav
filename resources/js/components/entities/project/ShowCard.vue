<script setup lang="ts">
import { BinaryBadge } from '@/components/badge';
import {
  CardContent,
  CardHeader,
  ContentGrid,
  ContentHighlight,
  ContentLine,
  EntityCard,
  StatBox,
} from '@/components/card';
import { _ } from '@/composables/useTranslations';
import { Calendar, Camera, Home, Shield, User, UsersRound } from 'lucide-vue-next';
import ProjectFooter from './shared/ProjectFooter.vue';

defineProps<{
  project: ApiResource<Project>;
}>();
</script>

<template>
  <EntityCard :resource="project" entity="project" type="show" class="group">
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

    <ProjectFooter :project />
  </EntityCard>
</template>
