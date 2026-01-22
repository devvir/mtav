<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import { Button } from '@/components/ui/button';
import type { PageProps } from '@inertiajs/core';
import { CreditCard } from 'lucide-vue-next';
import EntitySamples from './cards/EntitySamples.vue';
import EntitySection from './cards/EntitySection.vue';
import { useEntityCardsController } from './cards/useEntityCardsController';

const props = defineProps<
  PageProps & {
    projects: ApiResource<Project>[];
    admins: ApiResource<Admin>[];
    members: ApiResource<Member>[];
    families: ApiResource<Family>[];
    units: ApiResource<Unit>[];
    unitTypes: ApiResource<UnitType>[];
    media: ApiResource<Media>[];
    events: ApiResource<Event>[];
    logs: ApiResource<Log>[];
  }
>();

// Main controller - coordinates all the behavior
const {
  entities,
  isExpanded,
  handleSectionToggle,
  handleExpandAll,
  handleCollapseAll,
  initializeNavigation,
} = useEntityCardsController({
  projects: props.projects,
  admins: props.admins,
  members: props.members,
  families: props.families,
  units: props.units,
  unitTypes: props.unitTypes,
  media: props.media,
  events: props.events,
  logs: props.logs,
});

// Initialize navigation on mount
onMounted(() => {
  const cleanup = initializeNavigation();

  onUnmounted(() => {
    cleanup();
  });
});
</script>

<template>
  <Head title="Entity Cards" />

  <main class="mx-auto max-w-7xl space-y-6 py-8">
    <header>
      <Breadcrumbs>
        <Breadcrumb route="dev.dashboard" text="Dev" />
        <Breadcrumb route="dev.entity-cards" text="Cards" no-link />
      </Breadcrumbs>
    </header>

    <div class="px-4">
      <!-- Page Header -->
      <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <CreditCard class="h-8 w-8 text-blue-500" />
          <h1 class="text-3xl font-bold text-foreground">Entity Cards Preview</h1>
        </div>

        <div class="flex items-center gap-2">
          <Button @click="handleExpandAll" variant="outline" size="sm"> Expand All </Button>
          <Button @click="handleCollapseAll" variant="outline" size="sm"> Collapse All </Button>
        </div>
      </div>

      <p class="mb-8 text-gray-600">
        Interactive preview of all entity card components with both index and show card variations.
      </p>

      <!-- Entity Sections -->
      <div v-if="entities.length === 0" class="py-12 text-center text-gray-500">
        No entities with sample data found.
      </div>

      <div v-else class="space-y-6">
        <EntitySection
          v-for="entity in entities"
          :key="entity.key"
          :entity="entity"
          :is-expanded="isExpanded(entity.key)"
          @toggle="handleSectionToggle(entity.key)"
        >
          <EntitySamples :entity="entity" :entity-data="props[entity.propName]" />
        </EntitySection>
      </div>
    </div>
  </main>
</template>
