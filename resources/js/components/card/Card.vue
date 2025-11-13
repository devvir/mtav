<script setup lang="ts">
import { cn } from '@/lib/utils';
import { ModalLink } from '@inertiaui/modal-vue';
import * as exposed from './exposed';
import type { CardType } from '.';
import { entityRoutes } from '@/composables/useResources';
import { _ } from '@/composables/useTranslations';

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
    :class="cn(
      'w-full max-w-3xl [&>*]:p-4 border rounded-lg',
      'bg-surface-elevated border-border shadow-sm [&>*+*]:border-border-subtle',
      // Deleted resource styling
      resource.deleted_at && 'opacity-60 bg-surface border-red-200 shadow-sm',
      $attrs.class,
      'flex flex-col h-full [&>*+*]:border-t', // No override allowed
      linksToDetails && 'cursor-pointer transition-all hover:shadow-danger focus:outline-none focus:ring-2 focus:ring-focus-ring focus:ring-offset-2',
    )"
  >
    <slot />
  </component>
</template>