<script setup lang="ts">
// Copilot - pending review
import { Form } from '@/components/forms';
import { FormSpecs, FormType, SelectOptions } from '@/components/forms/types';
import { currentProject } from '@/composables/useProjects';

const props = defineProps<{
  type: FormType;
  action: string;
  title: string;
  projects: ApiResource<Project>[];
  family?: ApiResource<Family>; // For edit forms
}>();

const projectOptions: SelectOptions = {};
props.projects.forEach((project: Project) => (projectOptions[project.id] = project.name));

const formSpecs: FormSpecs = {
  project_id: {
    element: 'select',
    label: 'Project',
    selected: props.family?.project?.id ?? currentProject.value?.id,
    options: projectOptions,
    required: true,
    displayId: true,
    disabled: props.projects.length < 2,
  },
  name: { element: 'input', label: 'Family Name', value: props.family?.name, required: true },
};
</script>

<template>
  <Form
    v-bind="{ type, action, params: props.family?.id, title }"
    :specs="formSpecs"
    autocomplete="off"
  >
    <!-- <template v-slot:aside>
      Explain that moving a family to another project will move all of its members
    </template> -->
  </Form>
</template>
