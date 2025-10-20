<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { useRoute } from 'ziggy-js';

defineProps<{
  route: string;
  all: 0 | 1;
}>();

const inertiaRoute = useRoute();
</script>

<template>
  <div class="flex overflow-hidden rounded-2xl border-2 border-foreground/80 @xl:text-sm">
    <Link
      v-for="(showAll, label) in { Enabled: 0, All: 1 }"
      :key="showAll"
      as="button"
      :href="inertiaRoute(route, { showAll })"
      class="block w-full px-4 py-2 transition-colors lg:py-1"
      :tabindex="!showAll == !all ? -1 : 0"
      :class="
        !all == !showAll
          ? 'pointer-events-none bg-foreground/80 text-background'
          : 'cursor-pointer bg-muted text-muted-foreground/85 active:outline-0 hocus:bg-foreground/20'
      "
    >
      {{ _(label) }}
    </Link>
  </div>
</template>
