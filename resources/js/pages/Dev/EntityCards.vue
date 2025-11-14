<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import { Button } from '@/components/ui/button';
import {
  Building,
  Calendar,
  CreditCard,
  FileText,
  Home,
  Image,
  UserCheck,
  Users,
} from 'lucide-vue-next';

// Import entity card components
import AdminIndexCard from '@/components/entities/admin/IndexCard.vue';
import AdminShowCard from '@/components/entities/admin/ShowCard.vue';
import EventIndexCard from '@/components/entities/event/IndexCard.vue';
import EventShowCard from '@/components/entities/event/ShowCard.vue';
import FamilyIndexCard from '@/components/entities/family/IndexCard.vue';
import FamilyShowCard from '@/components/entities/family/ShowCard.vue';
import MediaIndexCard from '@/components/entities/media/IndexCard.vue';
import MediaShowCard from '@/components/entities/media/ShowCard.vue';
import LogIndexCard from '@/components/entities/log/IndexCard.vue';
import LogShowCard from '@/components/entities/log/ShowCard.vue';
import MemberIndexCard from '@/components/entities/member/IndexCard.vue';
import MemberShowCard from '@/components/entities/member/ShowCard.vue';
import ProjectIndexCard from '@/components/entities/project/IndexCard.vue';
import ProjectShowCard from '@/components/entities/project/ShowCard.vue';
import UnitIndexCard from '@/components/entities/unit/IndexCard.vue';
import UnitShowCard from '@/components/entities/unit/ShowCard.vue';
import EntitySection from './cards/EntitySection.vue';
import EntitySamples from './cards/EntitySamples.vue';

const props = defineProps<{
  projects: ApiResource<Project>[];
  admins: ApiResource<Admin>[];
  members: ApiResource<Member>[];
  families: ApiResource<Family>[];
  units: ApiResource<Unit>[];
  media: ApiResource<Media>[];
  events: ApiResource<Event>[];
  logs: ApiResource<Log>[];
}>();

// Define entities with their icons and metadata
const entities = [
  {
    key: 'projects',
    name: 'Projects',
    propName: 'projects',
    icon: Building,
    description: 'Housing cooperative projects managed by admins',
    color: 'text-blue-500',
    component: EntitySamples,
    indexCard: ProjectIndexCard,
    showCard: ProjectShowCard,
  },
  {
    key: 'units',
    name: 'Units',
    propName: 'units',
    icon: Home,
    description: 'Living units within each project',
    color: 'text-green-500',
    component: EntitySamples,
    indexCard: UnitIndexCard,
    showCard: UnitShowCard,
  },
  {
    key: 'admins',
    name: 'Admins',
    propName: 'admins',
    icon: UserCheck,
    description: 'Project administrators and managers',
    color: 'text-purple-500',
    component: EntitySamples,
    indexCard: AdminIndexCard,
    showCard: AdminShowCard,
  },
  {
    key: 'members',
    name: 'Members',
    propName: 'members',
    icon: Users,
    description: 'Family members participating in projects',
    color: 'text-indigo-500',
    component: EntitySamples,
    indexCard: MemberIndexCard,
    showCard: MemberShowCard,
  },
  {
    key: 'families',
    name: 'Families',
    propName: 'families',
    icon: Home,
    description: 'Family units (atomic participation units)',
    color: 'text-orange-500',
    component: EntitySamples,
    indexCard: FamilyIndexCard,
    showCard: FamilyShowCard,
  },
  {
    key: 'events',
    name: 'Events',
    propName: 'events',
    icon: Calendar,
    description: 'Project events and activities',
    color: 'text-rose-500',
    component: EntitySamples,
    indexCard: EventIndexCard,
    showCard: EventShowCard,
  },
  {
    key: 'logs',
    name: 'Logs',
    propName: 'logs',
    icon: FileText,
    description: 'System activity logs and audit trails',
    color: 'text-gray-500',
    component: EntitySamples,
    indexCard: LogIndexCard,
    showCard: LogShowCard,
  },
  {
    key: 'gallery',
    name: 'Gallery',
    propName: 'media',
    icon: Image,
    description: 'Photo galleries and media collections',
    color: 'text-pink-500',
    component: EntitySamples,
    indexCard: MediaIndexCard,
    showCard: MediaShowCard,
  },
];

// Track expanded state for each section
const expandedSections = ref<Record<string, boolean>>(
  entities.reduce(
    (acc, entity) => {
      acc[entity.key] = false;
      return acc;
    },
    {} as Record<string, boolean>,
  ),
);

// Function to toggle section expansion
const toggleSection = (entityKey: string) => {
  expandedSections.value[entityKey] = !expandedSections.value[entityKey];
};

// Function to expand all sections
const expandAll = () => {
  entities.forEach((entity) => {
    expandedSections.value[entity.key] = true;
  });
};

// Function to collapse all sections
const collapseAll = () => {
  entities.forEach((entity) => {
    expandedSections.value[entity.key] = false;
  });
};
</script>

<template>

  <Head title="Entity Cards Preview" />

  <Breadcrumbs global>
    <Breadcrumb route="dev.dashboard">Dev</Breadcrumb>
    <Breadcrumb route="dev.entity-cards" no-link>Entity Cards</Breadcrumb>
  </Breadcrumbs>

  <div class="container mx-auto max-w-7xl py-8">
    <div class="mb-8">
      <div class="mb-4 flex items-center gap-3">
        <CreditCard class="h-8 w-8 text-primary" />
        <h1 class="text-3xl font-bold">Entity Cards Preview</h1>
      </div>
      <p class="mb-6 text-text-muted">
        Preview of how the Card component will look when used with each application entity. This
        shows both "index cards" (for list views) and "show cards" (for detail views).
      </p>

      <!-- Expand/Collapse Controls -->
      <div class="mb-8 flex gap-3">
        <Button variant="outline" size="sm" @click="expandAll"> Expand All </Button>
        <Button variant="outline" size="sm" @click="collapseAll"> Collapse All </Button>
      </div>
    </div>

    <!-- Entity Sections -->
    <div class="space-y-6">
      <EntitySection v-for="entity in entities" :key="entity.key" :entity="entity" :entity-data="props[entity.propName]"
        :is-expanded="expandedSections[entity.key]" @toggle="toggleSection(entity.key)" />
    </div>

    <!-- Info Footer -->
    <div class="mt-12 rounded-lg bg-muted/50 p-6">
      <h3 class="mb-2 text-lg font-semibold">Next Steps</h3>
      <div class="space-y-2 text-sm text-text-muted">
        <p>
          This page provides a consolidated view of how the Card component will be used across all
          entities. Once the actual card implementations are added, this will serve as a visual
          reference for:
        </p>
        <ul class="ml-4 list-inside list-disc space-y-1">
          <li>Card component consistency across different entity types</li>
          <li>Responsive behavior in grid layouts</li>
          <li>Visual hierarchy and information density</li>
          <li>Icon and color scheme consistency</li>
        </ul>
        <p class="pt-2 text-xs text-amber-600 dark:text-amber-400">
          💡 The existing card preview page at <strong>/dev/cards</strong> shows the component
          system itself, while this page shows real-world entity usage.
        </p>
      </div>
    </div>
  </div>
</template>
