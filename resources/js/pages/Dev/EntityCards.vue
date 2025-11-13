<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import { Button } from '@/components/ui/button';
import { CreditCard, Building, Users, UserCheck, Home, Calendar, FileText, Image } from 'lucide-vue-next';

// Import entity card components
import AdminIndexCard from '@/components/entities/admin/IndexCard.vue';
import AdminShowCard from '@/components/entities/admin/ShowCard.vue';
import FamilyIndexCard from '@/components/entities/family/IndexCard.vue';
import FamilyShowCard from '@/components/entities/family/ShowCard.vue';
import ProjectIndexCard from '@/components/entities/project/IndexCard.vue';
import ProjectShowCard from '@/components/entities/project/ShowCard.vue';
import MemberIndexCard from '@/components/entities/member/IndexCard.vue';
import MemberShowCard from '@/components/entities/member/ShowCard.vue';
import EventIndexCard from '@/components/entities/event/IndexCard.vue';
import EventShowCard from '@/components/entities/event/ShowCard.vue';
import UnitIndexCard from '@/components/entities/unit/IndexCard.vue';
import UnitShowCard from '@/components/entities/unit/ShowCard.vue';
import LogIndexCard from '@/components/entities/log/IndexCard.vue';
import LogShowCard from '@/components/entities/log/ShowCard.vue';
import GalleryIndexCard from '@/components/entities/gallery/IndexCard.vue';
import GalleryShowCard from '@/components/entities/gallery/ShowCard.vue';
import GenericSamples from './cards/Samples.vue';
import EntitySection from './cards/EntitySection.vue';

// Define entities with their icons and metadata
const entities = [
  {
    key: 'projects',
    name: 'Projects',
    propName: 'project',
    icon: Building,
    description: 'Housing cooperative projects managed by admins',
    color: 'text-blue-500',
    component: GenericSamples,
    indexCard: ProjectIndexCard,
    showCard: ProjectShowCard,
  },
  {
    key: 'units',
    name: 'Units',
    propName: 'unit',
    icon: Home,
    description: 'Living units within each project',
    color: 'text-green-500',
    component: GenericSamples,
    indexCard: UnitIndexCard,
    showCard: UnitShowCard,
  },
  {
    key: 'admins',
    name: 'Admins',
    propName: 'admin',
    icon: UserCheck,
    description: 'Project administrators and managers',
    color: 'text-purple-500',
    component: GenericSamples,
    indexCard: AdminIndexCard,
    showCard: AdminShowCard,
  },
  {
    key: 'members',
    name: 'Members',
    propName: 'member',
    icon: Users,
    description: 'Family members participating in projects',
    color: 'text-indigo-500',
    component: GenericSamples,
    indexCard: MemberIndexCard,
    showCard: MemberShowCard,
  },
  {
    key: 'families',
    name: 'Families',
    propName: 'family',
    icon: Home,
    description: 'Family units (atomic participation units)',
    color: 'text-orange-500',
    component: GenericSamples,
    indexCard: FamilyIndexCard,
    showCard: FamilyShowCard,
  },
  {
    key: 'events',
    name: 'Events',
    propName: 'event',
    icon: Calendar,
    description: 'Project events and activities',
    color: 'text-rose-500',
    component: GenericSamples,
    indexCard: EventIndexCard,
    showCard: EventShowCard,
  },
  {
    key: 'logs',
    name: 'Logs',
    propName: 'log',
    icon: FileText,
    description: 'System activity logs and audit trails',
    color: 'text-gray-500',
    component: GenericSamples,
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
    component: GenericSamples,
    indexCard: GalleryIndexCard,
    showCard: GalleryShowCard,
  },
];

// Track expanded state for each section
const expandedSections = ref<Record<string, boolean>>(
  entities.reduce((acc, entity) => {
    acc[entity.key] = false;
    return acc;
  }, {} as Record<string, boolean>)
);

// Function to toggle section expansion
const toggleSection = (entityKey: string) => {
  expandedSections.value[entityKey] = !expandedSections.value[entityKey];
};

// Function to expand all sections
const expandAll = () => {
  entities.forEach(entity => {
    expandedSections.value[entity.key] = true;
  });
};

// Function to collapse all sections
const collapseAll = () => {
  entities.forEach(entity => {
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
      <div class="flex items-center gap-3 mb-4">
        <CreditCard class="h-8 w-8 text-primary" />
        <h1 class="text-3xl font-bold">Entity Cards Preview</h1>
      </div>
      <p class="text-text-muted mb-6">
        Preview of how the Card component will look when used with each application entity.
        This shows both "index cards" (for list views) and "show cards" (for detail views).
      </p>

      <!-- Expand/Collapse Controls -->
      <div class="flex gap-3 mb-8">
        <Button variant="outline" size="sm" @click="expandAll">
          Expand All
        </Button>
        <Button variant="outline" size="sm" @click="collapseAll">
          Collapse All
        </Button>
      </div>
    </div>

    <!-- Entity Sections -->
    <div class="space-y-6">
      <EntitySection
        v-for="entity in entities"
        :key="entity.key"
        :entity="entity"
        :is-expanded="expandedSections[entity.key]"
        @toggle="toggleSection(entity.key)"
      />
    </div>

    <!-- Info Footer -->
    <div class="mt-12 p-6 bg-muted/50 rounded-lg">
      <h3 class="text-lg font-semibold mb-2">Next Steps</h3>
      <div class="text-sm text-text-muted space-y-2">
        <p>
          This page provides a consolidated view of how the Card component will be used across all entities.
          Once the actual card implementations are added, this will serve as a visual reference for:
        </p>
        <ul class="list-disc list-inside space-y-1 ml-4">
          <li>Card component consistency across different entity types</li>
          <li>Responsive behavior in grid layouts</li>
          <li>Visual hierarchy and information density</li>
          <li>Icon and color scheme consistency</li>
        </ul>
        <p class="text-xs pt-2 text-amber-600 dark:text-amber-400">
          💡 The existing card preview page at <strong>/dev/cards</strong> shows the component system itself,
          while this page shows real-world entity usage.
        </p>
      </div>
    </div>
  </div>
</template>