<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { Link } from '@inertiajs/vue3';
import { route as routeUri } from 'ziggy-js';

defineProps<{
  route: string;
  text?: string;
  noLink?: boolean;
  noTranslation?: boolean;
  params?: number | string | any[] | Record<string, any>;
}>();
</script>

<template>
  <li v-if="text || $slots.default" class="overflow-hidden text-ellipsis whitespace-nowrap">
    <Link
      v-bind="$attrs"
      :href="routeUri(route, params)"
      :class="{ 'pointer-events-none': noLink }"
    >
      <slot>{{ noTranslation ? text : _(text) }}</slot>
    </Link>
  </li>
</template>
