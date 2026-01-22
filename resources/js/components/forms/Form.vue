<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { InertiaForm } from '@inertiajs/vue3';
import {
  FilteredOptionsSpecs,
  FilteredSelectOptions,
  FormSpecs,
  FormUpdateEvent,
  ValueType,
} from '.';
import MaybeModal from '../MaybeModal.vue';
import FormInput from './FormInput.vue';
import FormSelect from './FormSelect.vue';
import FormSubmit from './FormSubmit.vue';
import * as keys from './keys';

const emit = defineEmits<{
  update: [payload: FormUpdateEvent];
}>();

const props = defineProps<{
  type: 'create' | 'update' | 'delete';
  action: string;
  params: unknown;
  title: string;
  specs?: FormSpecs;
  buttonText?: string;
}>();

if (!useSlots().default && !props.specs) {
  throw new Error(_('Form requires either custom form content or a "specs" prop.'));
}

const formElements = {
  input: FormInput,
  select: FormSelect,
};

const name2component = (name: string) => formElements[name as keyof typeof formElements];

const type2button: Record<string, string> = {
  create: 'Create',
  update: 'Update',
  delete: 'Delete',
};

const formFields: Record<string, ValueType | ValueType[]> = props.specs
  ? Object.fromEntries(
      Object.entries(props.specs as FormSpecs).map(([k, v]) => [
        k,
        v.element === 'select' ? v.selected : v.value,
      ]),
    )
  : {}; // TODO : for inline definition (no specs), infer fields from v-model bindings in slot content

const form = useForm(formFields);

const dependencies: [string, string, FilteredSelectOptions][] = Object.entries(
  (props.specs ?? {}) as FormSpecs,
)
  .filter(([, spec]) => spec.element === 'select' && spec.filteredBy)
  .map(([name, spec]) => [
    name,
    (spec as FilteredOptionsSpecs).filteredBy,
    (spec as FilteredOptionsSpecs).options,
  ]);

const options = reactive<{ [key: string]: FilteredSelectOptions }>({});
const placeholders = reactive<{ [key: string]: string }>({});

// Handle dependant selects (e.g. those with project-scoped options)
watch(
  () => form,
  () => {
    dependencies.forEach(([name, filteredBy, allOptions]) => {
      const selectedParent = form[filteredBy] as string | number;
      options[name] = selectedParent ? (allOptions[selectedParent] ?? []) : [];

      // Set placeholder based on parent selection
      delete placeholders[name];

      if (!selectedParent && props.specs[filteredBy]) {
        const parentLabel = _(props.specs![filteredBy].label);
        placeholders[name] = _('Please select a {parent} first', { parent: parentLabel });
      }
    });
  },
  { immediate: true, deep: true },
);

// Emit update event when a form value changes
watch(form, (data: InertiaForm<object>) =>
  Object.entries(data).forEach(([field, value]) => {
    if (!(field in formFields) || value === formFields[field]) return;

    emit('update', { field, value });
    formFields[field] = value;
  }),
);

// Update form when specs change
watch(
  () => props.specs,
  (newSpecs: FormSpecs, old: FormSpecs) => {
    Object.entries(newSpecs!).forEach(([field, spec]) => {
      const newValue = spec.element === 'select' ? spec.selected : spec.value;
      const oldValue =
        old?.[field]?.element === 'select' ? old[field].selected : old?.[field]?.value;

      if (newValue !== oldValue) form[field] = newValue;
    });
  },
  { deep: true },
);

const submit = (onSuccess?: () => void) => {
  const method = props.type === 'create' ? 'post' : 'put';

  form.submit(method, route(props.action, props.params), {
    preserveScroll: true,
    preserveState: true,
    onSuccess,
  });
};

const pauseModalClosing = ref(false);

provide(keys.pauseModalClosing, (pause: boolean = true) => (pauseModalClosing.value = pause));
</script>

<template>
  <MaybeModal
    :close-explicitly="pauseModalClosing || form.isDirty"
    v-slot="{ close }: { close?: () => void }"
  >
    <slot name="header" />

    <form @submit.prevent="submit(close)">
      <div class="flex h-full flex-col justify-center gap-wide-y px-wide">
        <h1 class="pl-2 text-3xl leading-loose font-bold">{{ title }}</h1>

        <div class="@container/form grid w-full grid-cols-[auto_1fr] gap-y-3">
          <slot :form="form">
            <template v-for="({ element, ...def }, name) of specs" :key="name">
              <Component
                :is="name2component(element)"
                :name="<keyof typeof formFields>name"
                v-bind="
                  def.filteredBy
                    ? { ...def, options: options[name], placeholder: placeholders[name] }
                    : def
                "
                v-model="form[name] as ValueType"
                :error="form.errors[name]"
                @input="form.clearErrors(<keyof typeof formFields>name)"
              />
            </template>
          </slot>
        </div>

        <aside v-if="$slots.aside" class="mb-base w-full">
          <slot name="aside" />
        </aside>

        <FormSubmit :disabled="form.processing" class="text-right">
          {{ _(buttonText ?? type2button[props.type]) }}
        </FormSubmit>
      </div>
    </form>

    <slot name="footer" />
  </MaybeModal>
</template>
