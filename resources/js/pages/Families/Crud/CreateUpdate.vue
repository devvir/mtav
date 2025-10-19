<script setup lang="ts">
import { Form } from '@/components/forms';
import { FormSpecs, FormType, SelectOptions } from '@/components/forms/types';
import { currentProject } from '@/composables/useProjects';

const props = defineProps<{
  type: FormType;
  action: string;
  title: string;
  projects: Project[];
  family?: Family; // For edit forms
}>();

const projectOptions: SelectOptions = {};
props.projects.forEach((project) => (projectOptions[project.id] = project.name));

const formSpecs: FormSpecs = {
  project: {
    element: 'select',
    label: 'Project',
    selected: props.family?.project?.id ?? currentProject.value?.id,
    options: projectOptions,
    required: true,
    displayId: true,
    disabled: props.projects.length < 2,
  },
  name: { element: 'input', label: 'Family Name', required: true },
};
</script>

<template>
  <Form v-bind="{ type, action, title }" :specs="formSpecs" autocomplete="off">
    <template v-slot:aside>
      <!-- Explain that moving a family to another project will move all of its members -->
    </template>
  </Form>
</template>
