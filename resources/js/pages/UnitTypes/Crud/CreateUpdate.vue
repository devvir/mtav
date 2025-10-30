<script setup lang="ts">
// Copilot - pending review
import { Form } from '@/components/forms';
import { FormSpecs, FormType } from '@/components/forms/types';
import { currentProject } from '@/composables/useProjects';

const props = defineProps<{
  type: FormType;
  action: string;
  title: string;
  unit_type?: UnitType; // Edit-only
}>();

const formSpecs: FormSpecs = {
  name: {
    element: 'input',
    label: 'Name',
    value: props.unit_type?.name,
    required: true,
  },
  description: {
    element: 'input',
    label: 'Description',
    value: props.unit_type?.description,
  },
  project_id: {
    element: 'input',
    type: 'hidden',
    value: currentProject.value?.id,
  },
};
</script>

<template>
  <Form v-bind="{ type, action, params: props.unit_type?.id, title }" :specs="formSpecs" />
</template>
