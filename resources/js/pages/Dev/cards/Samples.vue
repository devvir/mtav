<script setup lang="ts">
import fixturesData from './fixtures.json';
// import { currentProject } from '@/composables/useProjects';

const props = defineProps<{
  entity: {
    key: string;
    name: string;
    propName: string;
    indexCard: any;
    showCard: any;
  };
}>();

// Get the collection from fixtures based on the entity key
const collection = fixturesData[props.entity.key as keyof typeof fixturesData] as ApiResource<any>[];

// Special handling for projects: replace first item with currentProject if available
// if (props.entity.key === 'projects' && currentProject.value) {
//   collection = [currentProject.value as ApiResource<any>, ...collection.slice(1)];
// }
</script>

<template>
  <div class="space-y-8">
    <!-- Index Cards Section -->
    <div>
      <h3 class="text-lg font-medium mb-4 flex items-center gap-2">
        <span class="inline-flex items-center justify-center w-6 h-6 bg-primary text-primary-foreground text-sm font-semibold rounded">
          I
        </span>
        Index Cards (List View)
      </h3>
      <div class="grid place-items-stretch gap-4 auto-rows-auto grid-cols-[repeat(auto-fill,minmax(350px,1fr))] md:grid-cols-[repeat(auto-fill,minmax(400px,1fr))] lg:grid-cols-[repeat(auto-fill,minmax(450px,1fr))] xl:grid-cols-[repeat(auto-fill,minmax(480px,1fr))]">
        <component
          :is="entity.indexCard"
          v-for="item in collection"
          :key="`index-${item.id}`"
          v-bind="{ [entity.propName]: item }"
        />
      </div>
    </div>

    <!-- Show Cards Section -->
    <div>
      <h3 class="text-lg font-medium mb-4 flex items-center gap-2">
        <span class="inline-flex items-center justify-center w-6 h-6 bg-secondary text-secondary-foreground text-sm font-semibold rounded">
          S
        </span>
        Show Cards (Detail View)
      </h3>
      <div class="grid place-items-stretch gap-4 auto-rows-auto grid-cols-[repeat(auto-fill,minmax(350px,1fr))] md:grid-cols-[repeat(auto-fill,minmax(400px,1fr))] lg:grid-cols-[repeat(auto-fill,minmax(450px,1fr))] xl:grid-cols-[repeat(auto-fill,minmax(480px,1fr))]">
        <component
          :is="entity.showCard"
          v-for="item in collection"
          :key="`show-${item.id}`"
          v-bind="{ [entity.propName]: item }"
        />
      </div>
    </div>
  </div>
</template>