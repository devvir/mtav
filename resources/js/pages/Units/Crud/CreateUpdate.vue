<script setup lang="ts">
import { Form } from '@/components/forms';
import { FormSpecs, FormType, SelectOptions } from '@/components/forms/types';
import { currentProject } from '@/composables/useProjects';
import { _ } from '@/composables/useTranslations';

const props = defineProps<{
  type: FormType;
  action: string;
  title: string;
  unit_types: UnitType[];
  unit?: Unit; // Edit-only
}>();

const unitTypeOptions: SelectOptions = {};
props.unit_types.forEach((unitType) => (unitTypeOptions[unitType.id] = `${unitType.name} (${unitType.description.toLowerCase()})`));

const formSpecs: FormSpecs = {
  identifier: {
    element: 'input',
    label: 'Unit Identifier',
    value: props.unit?.identifier,
    required: true,
  },
  unit_type_id: {
    element: 'select',
    label: 'Unit Type',
    selected: props.unit?.type?.id,
    options: unitTypeOptions,
    required: true,
    displayId: true,
  },
  project_id: {
    element: 'input',
    type: 'hidden',
    value: currentProject.value?.id,
  },
};
</script>

<template>
  <Form v-bind="{ type, action, params: props.unit?.id, title }" :specs="formSpecs" autocomplete="off" />
</template>
