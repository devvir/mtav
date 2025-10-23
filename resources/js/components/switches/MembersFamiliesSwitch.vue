<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { useRoute } from 'ziggy-js';

const route = useRoute();
const isActive = (page: string) => route().current(page);

const flushPrefetch = () => router.flushAll();
</script>

<template>
  <div class="grid grid-cols-[1fr_1fr] overflow-hidden rounded-2xl border-4 border-foreground/80 @xl:text-sm">
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
          : 'cursor-pointer bg-muted/30 text-muted-foreground/85 active:outline-0 hocus:text-muted-foreground'
      "
      @click="flushPrefetch"
    >
      {{ _(label) }}
    </Link>
  </div>
</template>
