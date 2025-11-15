<script setup lang="ts">
import { cn } from '@/lib/utils';
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import MaybeModal from '@/components/MaybeModal.vue';
import { entityLabel, entityNS, entityPlural } from '@/composables/useResources';
import { ModalWidth } from '@inertiaui/modal-vue';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings

const props = defineProps<{
  entity: AppEntity;
  resource: ApiResource;
  pageTitle?: string;
  modalWidth?: ModalWidth;
  class?: HTMLAttributes['class'];
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
    <Breadcrumb v-if="! pageTitle" :route="routeIndex" :text="indexName" no-translation />
    <Breadcrumb :route="routeShow" :params="resource.id">{{ title }}</Breadcrumb>
  </Breadcrumbs>

  <MaybeModal :max-width="modalWidth ?? 'xl'" class="h-auto">
    <component
      :is="ShowCard"
      v-bind="{ [entity]: resource }"
      :class="cn('mx-auto max-w-xl pt-4 pb-1 [&>*]:px-8!', $props.class)" />
  </MaybeModal>
</template>
