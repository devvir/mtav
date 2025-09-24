<script setup lang="ts">
import Form from '@/components/forms/Form.vue';
import FormInput from '@/components/forms/FormInput.vue';
import FormSelect from '@/components/forms/FormSelect.vue';
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import { _ } from '@/composables/useTranslations';

const tempModel = ref(_('Form under construction'));
</script>

<template>
  <Head title="Create Project" />

  <Breadcrumbs global>
    <Breadcrumb route="projects.index" text="Projects" />
    <Breadcrumb route="projects.create" text="New Project" />
  </Breadcrumbs>

  <Form type="create" action="projects.store" :title="_('Create a new Project')">
    <FormInput name="name" label="Name" required v-model="tempModel" />
    <FormInput name="description" label="Description" required v-model="tempModel" />
    <FormInput name="organization" label="Organization" required v-model="tempModel" />
    <FormSelect
      name="admin"
      label="Admin"
      :options="{}"
      required
      v-model="tempModel"
      :create="{ target: 'admins.create', legend: 'New Admin' }"
    />
  </Form>
</template>
