<script setup lang="ts">
import { Form } from '@/components/forms';
import { FormSpecs, FormType, SelectOptions } from '@/components/forms/types';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings

const props = defineProps<{
  type: FormType;
  action: string;
  title: string;
  admins: Admin[];
  project?: Project; // Edit-only
}>();

const adminOptions: SelectOptions = {};
props.admins.forEach((admin) => (adminOptions[admin.id] = `${admin.firstname} ${admin.lastname}`));

const formSpecs: FormSpecs = {
  name: { element: 'input', label: 'Name', value: props.project?.name, required: true },
  description: { element: 'input', label: 'Description', value: props.project?.description, required: true },
  organization: { element: 'input', label: 'Organization', value: props.project?.organization },
  admins: {
    element: 'select',
    multiple: true,
    label: 'Admins',
    options: adminOptions,
    selected: props.project?.admins?.map((a) => a.id) ?? [],
    create: { target: 'admins.create', legend: 'Create a new Admin' },
    required: true,
  },
};
</script>

<template>
  <Form
    v-bind="{ type, action, params: props.project?.id, title }"
    :specs="formSpecs"
    buttonText="Create"
    autocomplete="off"
  />
</template>
