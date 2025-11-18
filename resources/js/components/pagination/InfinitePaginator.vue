<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import InfiniteScroll from '@/components/pagination/InfiniteScroll.vue';
import NoItems from '@/components/NoItems.vue';

export type CardSize = 'xs' | 'sm' | 'md' | 'lg' | 'xl';

const props = defineProps<{
  list: ApiResources;
  loadable: string;
  cardSize?: CardSize;
  featured?: number | string;
  noItemsMessage?: string;
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
  <section v-if="list.data.length">
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

    <InfiniteScroll :pageSpecs="list" :loadable :params="{ q: usePage().props.q }" />
  </section>

  <NoItems v-else :message="noItemsMessage" />
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
