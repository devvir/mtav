<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { useRoute } from 'ziggy-js';

const route = useRoute();
const isActive = (page: string) => route().current(page);
</script>

<template>
  <div class="flex overflow-hidden rounded-2xl border-2 border-foreground/80 @xl:text-sm">
    <Link
      v-for="(routeName, label) in { Families: 'families.index', Members: 'members.index' }"
      :key="routeName"
      as="button"
      :href="route(routeName)"
      prefetch
      class="block w-full px-4 py-2 transition-colors lg:py-1"
      :tabindex="isActive(routeName) ? -1 : 0"
      :class="
        isActive(routeName)
          ? 'bg-foreground/80 text-background'
          : 'cursor-pointer bg-muted text-muted-foreground/85 active:outline-0 hocus:bg-foreground/20'
      "
    >
      {{ _(label) }}
    </Link>
  </div>
</template>
