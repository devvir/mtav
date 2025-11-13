<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import InfinitePaginator from '@/components/pagination/InfinitePaginator.vue';
import type { GridColsOverrides } from '@/components/pagination/InfinitePaginator.vue';
import { entityLabel, entityNS, entityPlural } from '@/composables/useResources';

const props = defineProps<{
  entity: AppEntity;
  resources: ApiResources;
  q?: string;
  pageTitle?: string;
  gridColsOverrides?: GridColsOverrides;
}>();

const title = props.pageTitle ?? entityLabel(props.entity, 'plural');
const route = `${entityNS(props.entity)}.index`;
const loadable = entityPlural(props.entity);

const IndexCard = defineAsyncComponent(
    () => import(`@/components/entities/${props.entity}/IndexCard.vue`)
);
</script>

<template>
  <Head :title no-translation />

  <Breadcrumbs>
    <Breadcrumb :route :text="title" />
  </Breadcrumbs>

  <InfinitePaginator
    :list="resources"
    :loadable
    :filter="q"
    :gridColsOverrides
  >
    <template v-slot:search-right>
      <slot name="search-right" />
    </template>

    <template v-slot:default="{ item }">
      <component :is="IndexCard" v-bind="{ [entity]: item }" />
    </template>
  </InfinitePaginator>
</template>
