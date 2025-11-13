<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import MaybeModal from '@/components/MaybeModal.vue';
import { entityLabel, entityNS, entityPlural } from '@/composables/useResources';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings

const props = defineProps<{
  entity: AppEntity;
  resource: ApiResource;
  pageTitle?: string;
}>();

const title = props.pageTitle ?? props.resource.name ?? entityLabel(props.entity);
const routeIndex = `${entityNS(props.entity)}.index`;
const routeShow = `${entityNS(props.entity)}.show`;
const indexName = entityPlural(props.entity);

const ShowCard = defineAsyncComponent(
  () => import(`@/components/entities/${props.entity}/ShowCard.vue`),
);
</script>

<template>
  <Head :title no-translation />

  <Breadcrumbs>
    <Breadcrumb :route="routeIndex" :text="indexName" no-translation />
    <Breadcrumb :route="routeShow" :params="resource.id">{{ title }}</Breadcrumb>
  </Breadcrumbs>

  <MaybeModal max-width="xl">
    <component :is="ShowCard" v-bind="{ [entity]: resource }" class="mx-auto max-w-xl p-base" />
  </MaybeModal>
</template>
