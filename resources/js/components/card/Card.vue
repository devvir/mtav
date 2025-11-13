<script setup lang="ts">
import { entityRoutes } from '@/composables/useResources';
import { _ } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';
import { ModalLink } from '@inertiaui/modal-vue';
import type { CardType } from '.';
import * as exposed from './exposed';

const props = defineProps<{
  entity: AppEntity;
  resource: ApiResource;
  linksToDetailsModal?: boolean;
  type?: CardType;
}>();

const routes = entityRoutes(props.entity);

const linksToDetails = computed(() => props.linksToDetailsModal || props.type === 'index');

provide(exposed.resource, props.resource);
provide(exposed.entity, props.entity);
provide(exposed.type, props.type);
provide(exposed.routes, routes);
</script>

<template>
  <component
    :is="linksToDetails ? ModalLink : 'article'"
    :href="linksToDetails ? route(routes.show, resource.id) : undefined"
    :prefetch="linksToDetails ? ($attrs.prefetch ?? 'hover') : undefined"
    :title="linksToDetails ? ($attrs.title ?? _('Click to see the Details Page')) : undefined"
    :class="
      cn(
        'w-full max-w-3xl rounded-lg border [&>*]:p-4',
        'border-border bg-surface-elevated shadow-sm [&>*+*]:border-border-subtle',
        // Deleted resource styling
        resource.deleted_at && 'border-red-200 bg-surface opacity-60 shadow-sm',
        $attrs.class,
        'flex h-full flex-col [&>*+*]:border-t', // No override allowed
        linksToDetails &&
          'cursor-pointer transition-all hover:shadow-danger focus:ring-2 focus:ring-focus-ring focus:ring-offset-2 focus:outline-none',
      )
    "
  >
    <slot />
  </component>
</template>
