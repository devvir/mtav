<script setup lang="ts">
import Head from '@/components/Head.vue';
import { FilterConfig, Filters, SEARCH } from '@/components/filtering';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import type { CardSize } from '@/components/pagination/InfinitePaginator.vue';
import InfinitePaginator from '@/components/pagination/InfinitePaginator.vue';
import { entityLabel, entityNS, entityPlural } from '@/composables/useResources';

const props = defineProps<{
  entity: AppEntity;
  resources: ApiResources;
  pageTitle?: string;
  filters?: FilterConfig;
  cardSize?: CardSize;
}>();

const title = props.pageTitle ?? entityLabel(props.entity, 'plural');
const route = `${entityNS(props.entity)}.index`;
const loadable = entityPlural(props.entity);

const IndexCard = defineAsyncComponent(
  () => import(`@/components/entities/${props.entity}/IndexCard.vue`),
);

const filtersConfig = computed(
  () => props.filters ?? { q: { type: SEARCH, value: usePage().props.q } },
);
</script>

<template>
  <Head :title no-translation />

  <Breadcrumbs>
    <Breadcrumb :route :text="title" />
  </Breadcrumbs>

  <slot>
    <Filters :config="filtersConfig" auto-filter />
  </slot>

  <InfinitePaginator :list="resources" :loadable :cardSize>
    <template v-slot="{ item }">
      <component :is="IndexCard" v-bind="{ [entity]: item }" />
    </template>
  </InfinitePaginator>
</template>
