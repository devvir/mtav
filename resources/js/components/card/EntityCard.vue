<script setup lang="ts">
import { entityRoutes } from '@/composables/useResources';
import { _ } from '@/composables/useTranslations';
import type { CardType } from '.';
import * as exposed from './exposed';
import Card from './Card.vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
  resource: ApiResource;
  entity: AppEntity;
  type?: CardType;
  dimmed?: boolean;
  cardLink?: string;
}>();

const routes = entityRoutes(props.entity);
const detailsRoute = route(routes.show, props.resource.id);

provide(exposed.resource, props.resource);
provide(exposed.entity, props.entity);
provide(exposed.type, props.type);
provide(exposed.routes, routes);
</script>

<template>
  <Card :dimmed
  :cardLink="cardLink ?? (type === 'index' ? detailsRoute : undefined)"
  :class="cn(
    { 'border-red-200 bg-surface opacity-60 shadow-sm': resource.deleted_at },
    $attrs.class,
  )">
    <slot :card-type="type" :entity-routes="routes" />
  </Card>
</template>
