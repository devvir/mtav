<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import { entityLabel, entityRoutes } from '@/composables/useResources';
import { Form, type FormServiceData } from '../forms';

const props = defineProps<{
  form: FormServiceData;
  title?: string;
}>();

const indexRoute = entityRoutes(props.form.entity).index;
const indexTitle = entityLabel(props.form.entity, 'plural');
</script>

<template>
  <Head :title="title ?? form.title" no-translation />

  <Breadcrumbs>
    <Breadcrumb :route="indexRoute" :text="indexTitle" />
    <Breadcrumb :route="form.action.route" :params="form.action.params" :text="form.title" />
  </Breadcrumbs>

  <div class="h-auto max-w-5xl mx-auto">
    <Form
      :type="form.type"
      :action="form.action.route"
      :params="form.action.params"
      :title="title ?? form.title"
      :specs="form.specs"
      autocomplete="off"
    />

    <slot />
  </div>

</template>
