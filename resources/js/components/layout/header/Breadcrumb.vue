<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
  route: string;
  text?: string;
  noLink?: boolean;
  params?: number | string | any[] | Record<string, any>;
}>();

const href = computed(() => route(props.route, props.params));
</script>

<template>
  <li v-if="props.text || $slots.default" class="overflow-hidden text-ellipsis whitespace-nowrap">
    <Link v-bind="$attrs" :href="href" :class="{ 'pointer-events-none': noLink }">
      <slot>{{ _(text as string) }}</slot>
    </Link>
  </li>
</template>
