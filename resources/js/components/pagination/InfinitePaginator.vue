<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import InfiniteScroll from '@/components/pagination/InfiniteScroll.vue';
import NoItems from '@/components/NoItems.vue';
import { Card } from '@/components/card';

export type CardSize = 'xs' | 'sm' | 'md' | 'lg' | 'xl' | 'full';

const props = defineProps<{
  resources: MaybeDeferred<ApiResources>;
  loadable: string;
  cardSize?: CardSize;
  featured?: number | string;
  noItemsMessage?: string;
}>();

// Skeleton cards to show on first page load only
let loadingCards = Array.from({ length: Math.floor(5 + Math.random() * 5) }, (_, i) => ({ id: i }));
watch(() => props.resources , () => loadingCards = []);

const resourcesList = computed(() => props.resources?.data ?? loadingCards);

const size: Record<CardSize, number> = { xs: 0.8, sm: 0.9, md: 1, lg: 1.2, xl: 1.4, full: 1 };
const cardSizeMultiplier = computed<number>(() => size[(props.cardSize ?? 'md') as CardSize]);
</script>

<template>
  <section v-if="resourcesList.length">
    <TransitionGroup name="fade" tag="ul" :appear="!! resources"
      class="grid list-none place-items-stretch sm:auto-rows-auto"
      :class="cardSize === 'full'
        ? 'gap-2'
        : '@md:grid-cols-[repeat(auto-fill,minmax(calc(min(20rem,calc(15rem+4.5cqw))*var(--card-size-multiplier)),1fr))] gap-base'"
      :style="{ '--card-size-multiplier': cardSizeMultiplier }">
      <li
        v-for="item in resourcesList"
        :key="item.id"
        class="h-full transition-all not-hocus:opacity-90 hocus:scale-101"
        :class="featured === item.id ? 'col-start-1 -col-end-1 row-start-1 w-full pb-base' : ''"
      >
        <!-- Actual Item Card, once data is available -->
        <slot v-if="resources" :item />

        <!-- Loading Skeleton Card -->
        <!-- TODO: extract to IndexSkeletonCard (placeholder closer to the real Cards) -->
        <div v-else class="animate animate-pulse h-40"><Card class="opacity-60" /></div>
      </li>
    </TransitionGroup>

    <InfiniteScroll v-if="resources" :pageSpecs="resources" :loadable :params="{ q: usePage().props.q }" />
  </section>

  <NoItems v-else-if="resources" :message="noItemsMessage" />
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
