<script setup lang="ts">
import { Form } from '@/components/forms';
import { FormSpecs, FormType, SelectOptions } from '@/components/forms/types';
import { currentUser, iAmNotAdmin } from '@/composables/useAuth';
import { currentProject } from '@/composables/useProjects';
import { _ } from '@/composables/useTranslations';

const props = defineProps<{
  type: FormType;
  action: string;
  title: string;
  projects: Project[];
  families: Family[];
  user?: User; // Edit-only
}>();

const projectOptions: SelectOptions = {};
props.projects.forEach((project) => (projectOptions[project.id] = project.name));

const familyOptions: SelectOptions = {};
props.families.forEach((family) => (familyOptions[family.id] = `${_('Family')}: ${family.name}`));

const formSpecs: FormSpecs = {
  project: {
    element: 'select',
    label: 'Project',
    selected: props.user?.project?.id ?? currentProject.value?.id,
    options: projectOptions,
    required: true,
    displayId: true,
    disabled: props.projects.length < 2,
  },
  family: {
    element: 'select',
    label: 'Family',
    selected: props.user?.family.id,
    options: familyOptions,
    required: true,
    displayId: true,
    disabled: props.type === 'edit',
    create: { target: 'families.create', legend: 'Add a new Family' },
  },
  email: {
    element: 'input',
    label: 'Email',
    type: 'email',
    autocomplete: false,
    required: true,
  },
  firstname: { element: 'input', label: 'First Name', required: true },
  lastname: { element: 'input', label: 'Last Name' },
};

/**
 * Members cannot choose project or family; these are set automatically
 */
if (iAmNotAdmin.value) {
  formSpecs.project = { element: 'input', type: 'hidden', value: currentProject.value?.id };
  formSpecs.family = { element: 'input', type: 'hidden', value: currentUser.value?.family?.id };
}
</script>

<template>
  <Form v-bind="{ type, action, title }" :specs="formSpecs" buttonText="Invite" autocomplete="off">
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
              ? _('Changes to the email remain on hold until the user confirms them')
              : _('The user will be sent a link to complete their registration')
          }}
        </li>
        <li class="list-item @md:leading-wide">
          {{ _('Double-check the email, as it will serve to authenticate the user') }}
        </li>
      </ul>
    </template>
  </Form>
</template>
