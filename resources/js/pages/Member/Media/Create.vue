<script setup lang="ts">
import Field from '@/components/form/Field.vue';
import FileDrop from '@/components/form/FileDrop.vue';
import FormActions from '@/components/form/FormActions.vue';
import TextInput from '@/components/form/TextInput.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{
  category: string | null;
  accept: Record<string, string[]>;
  maxFileKb: number;
  maxFiles: number;
  storage: { used_mb: number; limit_mb: number } | null;
}>();

const form = useForm<{ description: string; files: File[] }>({ description: '', files: [] });

const accept = computed(() => Object.values(props.accept).flat().join(','));
const fileErrors = computed(() =>
  Object.entries(form.errors)
    .filter(([key]) => key.startsWith('files'))
    .map(([, message]) => message)
    .join(' '),
);

const submit = () => form.post(route('media.store'), { forceFormData: true });
</script>

<template>
  <PageHeader
    :title="_('Share files')"
    :back="{ href: route('media.index', category ? { category } : {}), label: _('Files') }"
  />

  <form
    class="max-w-2xl space-y-5 rounded-2xl border-2 border-border bg-surface p-5 sm:p-6"
    @submit.prevent="submit"
  >
    <Field
      :label="_('Files')"
      :hint="
        _('Photos, videos, audio, PDF and office documents. Up to {count} at once.', {
          count: maxFiles,
        })
      "
      :error="fileErrors"
      required
    >
      <FileDrop
        name="files" v-model="form.files"
        :accept
        multiple
        :max-files="maxFiles"
        :max-file-kb="maxFileKb"
        :invalid="Boolean(fileErrors)"
      />
    </Field>

    <Field
      :label="_('Description')"
      :hint="_('What it is, for everyone browsing the files.')"
      :error="form.errors.description"
      required
      v-slot="field"
    >
      <TextInput
        v-bind="field"
        name="description" v-model="form.description"
        :placeholder="_('Building site, week 12')"
      />
    </Field>

    <p v-if="storage" class="text-sm text-text-muted">
      {{
        _('The project uses {used} of its {limit} MB until it is approved.', {
          used: storage.used_mb,
          limit: storage.limit_mb,
        })
      }}
    </p>

    <div
      v-if="form.progress"
      class="h-2 overflow-hidden rounded-full bg-surface-sunken"
      role="progressbar"
      :aria-valuenow="form.progress.percentage"
    >
      <div
        class="h-full bg-interactive transition-all"
        :style="{ width: `${form.progress.percentage}%` }"
      />
    </div>

    <FormActions
      :submit-label="_('Share')"
      :processing="form.processing"
      :cancel-href="route('media.index')"
    />
  </form>
</template>
