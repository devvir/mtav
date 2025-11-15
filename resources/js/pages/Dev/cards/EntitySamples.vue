<script setup lang="ts">
const props = defineProps<{
  entity: {
    key: string;
    name: string;
    propName: string;
    singularPropName: string;
    indexCard: any;
    showCard: any;
  };
  entityData: ApiResource[];
}>();

// Use the real entity data from the controller
const collection = props.entityData;
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
          v-bind="{ [entity.singularPropName]: item }"
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
          v-bind="{ [entity.singularPropName]: item }"
        />
      </div>
    </div>
  </div>
</template>
