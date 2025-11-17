<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { InertiaForm } from '@inertiajs/vue3';
import MaybeModal from '../MaybeModal.vue';
import FormInput from './FormInput.vue';
import FormSelect from './FormSelect.vue';
import FormSubmit from './FormSubmit.vue';
import * as keys from './keys';
import { FormSpecs, FormUpdateEvent, ValueType } from './types';

const emit = defineEmits<{
  update: [payload: FormUpdateEvent];
}>();

const props = defineProps<{
  type: string;
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
  edit: 'Update',
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
  const method = props.type === 'edit' ? 'put' : 'post';

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
    <form @submit.prevent="submit(close)">
      <div class="flex h-full flex-col justify-center gap-[calc(var(--spacing-wide-y)*2)] px-wide">
        <h1 class="pl-2 text-3xl leading-loose font-bold">{{ title }}</h1>

        <div class="@container/form grid w-full grid-cols-[auto_1fr] gap-y-4">
          <slot :form="form">
            <template v-for="({ element, ...def }, name) of specs" :key="name">
              <Component
                :is="name2component(element)"
                :name="<keyof typeof formFields>name"
                v-bind="{ ...def }"
                v-model="form[name] as ValueType"
                :error="form.errors[name]"
                @input="form.clearErrors(<keyof typeof formFields>name)"
              />
            </template>
          </slot>
        </div>

        <aside
          v-if="$slots.aside"
          class="space-y-4 rounded-xl bg-surface-sunken px-5 py-4 text-text"
        >
          <slot name="aside" />
        </aside>

        <div class="text-right">
          <FormSubmit :disabled="form.processing">
            {{ _(buttonText ?? type2button[props.type]) }}
          </FormSubmit>
        </div>
      </div>
    </form>
  </MaybeModal>
</template>
