<script setup lang="ts">
import IndexSearch from '@/components/pagination/IndexSearch.vue';
import { _ } from '@/composables/useTranslations';
import AppSidebarHeaderSlot from '../layout/header/AppSidebarHeaderSlot.vue';
import InfiniteScroll from './InfiniteScroll.vue';

const props = defineProps<{
  loadable: string;
  list: PaginatedResources;
  filter: string;
  gridColsOverrides?: { sm?: string; md?: string; lg?: string; xl?: string };
}>();

const gridColsDefaults = {
  sm: 'sm:grid-cols-[repeat(auto-fill,minmax(290px,1fr))]',
  md: 'md:grid-cols-[repeat(auto-fill,minmax(338px,1fr))]',
  lg: 'lg:grid-cols-[repeat(auto-fill,minmax(375px,1fr))]',
  xl: 'xl:grid-cols-[repeat(auto-fill,minmax(400px,1fr))]',
};

const gridClasses = computed(() => Object.values(Object.assign({}, gridColsDefaults, props.gridColsOverrides ?? {})));
</script>

<template>
  <AppSidebarHeaderSlot>
    <IndexSearch :q="filter" class="px-base py-wide md:pt-0">
      <template v-slot:right>
        <slot name="search-right" />
      </template>
    </IndexSearch>
  </AppSidebarHeaderSlot>

  <section v-if="!list.data.length" class="flex size-full items-center justify-center text-xl">
    {{ _('No results') }}
  </section>

  <section>
    <TransitionGroup
      name="fade"
      tag="ul"
      appear
      class="grid list-none place-items-stretch gap-wide sm:auto-rows-auto"
      :class="gridClasses"
    >
      <li v-for="item in list.data" :key="item.id" class="h-full transition-all not-hocus:opacity-90 hocus:scale-102">
        <slot :item="item" />
      </li>
    </TransitionGroup>
  </section>

  <InfiniteScroll :pagination="list" :loadable="loadable" :params="{ q: filter }" />
</template>

<style scoped>
.fade-move,
.fade-enter-active,
.fade-leave-active {
  transition: all 100ms ease-in-out;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-100%);
  filter: grayscale(100%);
}
</style>
