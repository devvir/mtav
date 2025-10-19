<script setup lang="ts">
import { Form } from '@/components/forms';
import { FormSpecs, FormType, SelectOptions } from '@/components/forms/types';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings

const props = defineProps<{
  type: FormType;
  action: string;
  title: string;
  admins: User[];
  project?: Project; // Edit-only
}>();

const adminOptions: SelectOptions = {};
props.admins.forEach((admin) => (adminOptions[admin.id] = `${admin.firstname} ${admin.lastname}`));

const formSpecs: FormSpecs = {
  name: { element: 'input', label: 'Name', required: true },
  description: { element: 'input', label: 'Description', required: true },
  organization: { element: 'input', label: 'Organization', required: true },
  admins: {
    element: 'select',
    multiple: true,
    label: 'Admins',
    options: adminOptions,
    selected: props.project?.admins ? props.project.admins.map((a) => a.id) : [],
    create: { target: 'admins.create', legend: 'Create a new Admin' },
    required: true,
  },
};
</script>

<template>
  <Form v-bind="{ type, action, title }" :specs="formSpecs" buttonText="Create" autocomplete="off" />
</template>
