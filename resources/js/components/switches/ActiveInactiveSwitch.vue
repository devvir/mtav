<script setup lang="ts">
// Copilot - pending review
import { _ } from '@/composables/useTranslations';
import { useRoute } from 'ziggy-js';

defineProps<{
  route: string;
  all: boolean;
}>();

const inertiaRoute = useRoute();
</script>

<template>
  <div
    class="grid grid-cols-[1fr_1fr] overflow-hidden rounded-2xl border-4 border-foreground/80 @xl:text-sm"
  >
    <Link
      v-for="(showAll, label) in { Enabled: false, All: true }"
      :key="showAll"
      as="button"
      :href="inertiaRoute(route, { showAll })"
      class="block min-h-[44px] w-full px-4 py-2 transition-colors lg:py-1 @md:min-h-[36px]"
      :tabindex="showAll === all ? -1 : 0"
      :class="
        showAll === all
          ? 'pointer-events-none bg-foreground/80 text-background'
          : 'cursor-pointer bg-surface-sunken text-text-muted active:outline-0 hocus:text-text'
      "
    >
      {{ _(label) }}
    </Link>
  </div>
</template>
