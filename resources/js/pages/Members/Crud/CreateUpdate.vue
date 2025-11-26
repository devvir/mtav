<script setup lang="ts">
import { Form } from '@/components/forms';
import {
  FormSpecs,
  FormType,
  FormUpdateEvent,
  SelectOptions,
  SelectSpecs,
} from '@/components/forms/types';
import { currentUser, iAmNotAdmin } from '@/composables/useAuth';
import { currentProject } from '@/composables/useProjects';
import { _ } from '@/composables/useTranslations';

const props = defineProps<{
  type: FormType;
  action: string;
  title: string;
  projects: ApiResource<Project>[];
  families: ApiResource<Family>[];
  member?: ApiResource<Member>; // Edit-only
}>();

const projectOptions: SelectOptions = {};
props.projects.forEach((project: Project) => (projectOptions[project.id] = project.name));

const projectId = ref(props.member?.project?.id ?? currentProject.value?.id);

const specs: FormSpecs = {
  project_id: {
    element: 'select',
    label: 'Project',
    selected: projectId.value,
    options: projectOptions,
    required: true,
    displayId: true,
    disabled: props.projects.length < 2,
  },
  family_id: {
    element: 'select',
    label: 'Family',
    options: {},
    required: true,
    displayId: true,
    disabled: props.type === 'edit',
    create: { target: 'families.create', legend: 'Add a new Family' },
  },
  firstname: {
    element: 'input',
    label: 'First Name',
    value: props.member?.firstname,
    required: true,
    minlength: 2,
  },
  lastname: { element: 'input', label: 'Last Name', value: props.member?.lastname, minlength: 2 },
  email: {
    element: 'input',
    label: 'Email',
    type: 'email',
    value: props.member?.email,
    autocomplete: false,
    required: true,
  },
};

/**
 * Members cannot choose project or family; these are set automatically
 */
if (props.type === 'edit' || iAmNotAdmin.value) {
  const family = props.type === 'edit' ? props.member.family : (currentUser.value as Member).family;

  specs.project_id = { element: 'input', type: 'hidden', value: currentProject.value?.id };
  specs.family_id = { element: 'input', type: 'hidden', value: family.id };
}

const handleFormChange = ({ field, value }: FormUpdateEvent) => {
  if (field === 'project_id') projectId.value = parseInt(value as string);
};

// Filter families based on selected projectId
watchEffect(() => {
  const options: Record<number, string> = {};

  props.families
    .filter((family: Family) => family.project.id === projectId.value)
    .forEach((family: Family) => (options[family.id] = family.name));

  (specs.family_id as SelectSpecs).options = options;
  (specs.family_id as SelectSpecs).selected = null;
});
</script>

<template>
  <Form
    v-bind="{ type, action, params: member?.id, title }"
    :specs="specs"
    :buttonText="type === 'edit' ? undefined : 'Invite'"
    autocomplete="off"
    @update="handleFormChange"
  >
    <template v-slot:aside>
      <h2 class="font-semibold text-foreground/60 uppercase text-shadow-2xs text-shadow-danger/20">
        {{ _('Keep in mind') }}
      </h2>
      <ul class="list-inside list-disc text-base text-foreground/80">
        <li class="list-item leading-tight @md:leading-wide">
          {{
            type === 'edit'
              ? _('Changes to the email remain on hold until the user confirms them')
              : _('The user will be sent a link to complete their registration')
          }}
        </li>
        <li class="list-item @md:leading-wide">
          {{ _('Double-check the email, as it will serve to authenticate the User') }}
        </li>
      </ul>
    </template>
  </Form>
</template>
