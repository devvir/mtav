<script setup lang="ts">
import { Form } from '@/components/forms';
import { FormSpecs, FormType, SelectOptions } from '@/components/forms/types';
import { currentProject } from '@/composables/useProjects';
import { _ } from '@/composables/useTranslations';

const props = defineProps<{
  type: FormType;
  action: string;
  title: string;
  projects: Project[];
  admin?: Admin; // Edit-only
}>();

const projectOptions: SelectOptions = {};
props.projects.forEach((project) => (projectOptions[project.id] = project.name));

const formSpecs: FormSpecs = {
  project: {
    element: 'select',
    label: 'Project',
    selected: props.admin?.project?.id ?? currentProject.value?.id,
    options: projectOptions,
    required: true,
    displayId: true,
    disabled: props.projects.length < 2,
  },
  email: {
    element: 'input',
    label: 'Email',
    type: 'email',
    value: props.admin?.email,
    autocomplete: false,
    required: true,
  },
  firstname: { element: 'input', label: 'First Name', value: props.admin?.firstname, required: true },
  lastname: { element: 'input', label: 'Last Name', value: props.admin?.lastname },
};
</script>

<template>
  <Form
    v-bind="{ type, action, params: props.admin?.id, title }"
    :specs="formSpecs"
    buttonText="Invite"
    autocomplete="off"
  >
    <template v-slot:aside>
      <h2
        class="text-xl font-semibold tracking-wider text-accent-foreground uppercase text-shadow-2xs text-shadow-danger/20"
      >
        {{ _('Keep in mind') }}
      </h2>
      <ul class="list-inside list-disc space-y-1 text-base">
        <li class="list-item leading-tight @md:leading-wide">
          {{
            type === 'edit'
              ? _('Changes to the email remain on hold until the Admin confirms them')
              : _('The Admin will be sent a link to complete their registration')
          }}
        </li>
        <li class="list-item @md:leading-wide">
          {{ _('Double-check the email, as it will serve to authenticate the Admin') }}
        </li>
      </ul>
    </template>
  </Form>
</template>
