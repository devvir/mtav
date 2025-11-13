<script setup lang="ts">
import IndexSearch from '@/components/pagination/IndexSearch.vue';
import { _ } from '@/composables/useTranslations';
import AppSidebarHeaderSlot from '../layout/header/AppSidebarHeaderSlot.vue';
import InfiniteScroll from './InfiniteScroll.vue';

export type CardSize = 'xs' | 'sm' | 'md' | 'lg' | 'xl';

const props = defineProps<{
  loadable: string;
  list: ApiResources;
  filter: string;
  cardSize?: CardSize;
  featured?: number | string;
}>();

const sizeMultipliers: Record<CardSize, number> = {
  xs: 0.8,
  sm: 0.9,
  md: 1,
  lg: 1.2,
  xl: 1.4,
};

const cardSizeMultiplier = computed<number>(
  () => sizeMultipliers[(props.cardSize ?? 'md') as CardSize],
);
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
      class="grid list-none place-items-stretch gap-wide sm:auto-rows-auto @md:grid-cols-[repeat(auto-fill,minmax(calc(min(20rem,calc(15rem+4.5cqw))*var(--card-size-multiplier)),1fr))]"
      :style="{ '--card-size-multiplier': cardSizeMultiplier }"
    >
      <li
        v-for="item in list.data"
        :key="item.id"
        class="h-full transition-all not-hocus:opacity-90 hocus:scale-101"
        :class="featured === item.id ? 'col-start-1 -col-end-1 row-start-1 w-full pb-base' : ''"
      >
        <slot :item="item" />
      </li>
    </TransitionGroup>
  </section>

  <InfiniteScroll :pageSpecs="list" :loadable="loadable" :params="{ q: filter }" />
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
