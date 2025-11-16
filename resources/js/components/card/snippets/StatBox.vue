<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { entityLabel, entityRoutes } from '@/composables/useResources';
import { _ } from '@/composables/useTranslations';

const props = defineProps<{
  entity?: AppEntity;
  label?: string;
  count: number;
  icon: Component;
  iconColor: string;
}>();

if (! props.entity && ! props.label) {
  throw new Error(_('StatBox requires either the "entity" or the "label" prop.'));
}

const labelText = computed(() => props.label ?? entityLabel(props.entity, props.count));
const routeName = computed(() => props.entity ? entityRoutes(props.entity).index : null);
</script>

<template>
  <component
    :is="routeName ? Link : 'div'"
    :href="routeName ? route(routeName) : undefined"
    prefetch
    class="rounded-lg border border-border bg-surface-elevated p-3 text-center"
    :class="routeName ? 'transition-colors hover:bg-surface-elevated/80 cursor-pointer' : ''"
  >
    <div class="mb-1 flex items-center justify-center gap-2">
      <component :is="icon" class="size-5" :class="iconColor" />
      <div class="text-lg font-semibold text-text">
        {{ count }}
      </div>
    </div>
    <div class="text-xs text-text-muted">
      {{ labelText }}
    </div>
  </component>
</template>