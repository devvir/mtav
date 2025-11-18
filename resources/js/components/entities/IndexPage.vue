<script setup lang="ts">
import Head from '@/components/Head.vue';
import { FilterConfig, Filters, SEARCH } from '@/components/filtering';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import type { CardSize } from '@/components/pagination/InfinitePaginator.vue';
import InfinitePaginator from '@/components/pagination/InfinitePaginator.vue';
import { entityLabel, entityNS } from '@/composables/useResources';
import { _ } from '@/composables/useTranslations';

const props = defineProps<{
  entity: AppEntity;
  resources: ApiResources;
  pageTitle?: string;
  filters?: FilterConfig;
  cardSize?: CardSize;
}>();

const route = `${entityNS(props.entity)}.index`;
const loadable = entityNS(props.entity);
const search = ref(usePage().props.q);
const entitiesLabel = entityLabel(props.entity, 'plural');
const title = props.pageTitle ?? entitiesLabel;

const noItemsMessage = computed(() => {
  return search
    ? _('There are no {entities} matching your search', { entities: entitiesLabel })
    : _('There are no {entities} to display', { entities: entitiesLabel });
});

const IndexCard = defineAsyncComponent(
  () => import(`@/components/entities/${props.entity}/IndexCard.vue`),
);

const filtersConfig = computed(
  () => props.filters ?? { q: { type: SEARCH, value: search.value } },
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

  <InfinitePaginator :list="resources" :loadable :cardSize :no-items-message="noItemsMessage">
    <template v-slot="{ item }">
      <component :is="IndexCard" v-bind="{ [entity]: item }" />
    </template>
  </InfinitePaginator>
</template>
