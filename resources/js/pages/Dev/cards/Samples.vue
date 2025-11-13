<script setup lang="ts">
import fixturesData from './fixtures.json';

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
const collection = fixturesData[
  props.entity.key! as unknown as keyof typeof fixturesData
] as unknown as ApiResource[];

// Special handling for projects: replace first item with currentProject if available
// if (props.entity.key === 'projects' && currentProject.value) {
//   collection = [currentProject.value as ApiResource<any>, ...collection.slice(1)];
// }
</script>

<template>
  <div class="space-y-8">
    <!-- Index Cards Section -->
    <div>
      <h3 class="mb-4 flex items-center gap-2 text-lg font-medium">
        <span
          class="inline-flex h-6 w-6 items-center justify-center rounded bg-primary text-sm font-semibold text-primary-foreground"
        >
          I
        </span>
        Index Cards (List View)
      </h3>
      <div
        class="grid auto-rows-auto grid-cols-[repeat(auto-fill,minmax(350px,1fr))] place-items-stretch gap-4 md:grid-cols-[repeat(auto-fill,minmax(400px,1fr))] lg:grid-cols-[repeat(auto-fill,minmax(450px,1fr))] xl:grid-cols-[repeat(auto-fill,minmax(480px,1fr))]"
      >
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
      <h3 class="mb-4 flex items-center gap-2 text-lg font-medium">
        <span
          class="inline-flex h-6 w-6 items-center justify-center rounded bg-secondary text-sm font-semibold text-secondary-foreground"
        >
          S
        </span>
        Show Cards (Detail View)
      </h3>
      <div
        class="grid auto-rows-auto grid-cols-[repeat(auto-fill,minmax(350px,1fr))] place-items-stretch gap-4 md:grid-cols-[repeat(auto-fill,minmax(400px,1fr))] lg:grid-cols-[repeat(auto-fill,minmax(450px,1fr))] xl:grid-cols-[repeat(auto-fill,minmax(480px,1fr))]"
      >
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
