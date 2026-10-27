<script setup lang="ts">
import CheckboxField from '@/components/form/CheckboxField.vue';
import Field from '@/components/form/Field.vue';
import FormActions from '@/components/form/FormActions.vue';
import TextArea from '@/components/form/TextArea.vue';
import TextInput from '@/components/form/TextInput.vue';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';
import type { ProjectEvent } from '@/types/models';
import { useForm } from '@inertiajs/vue3';
import { MapPin, Video } from 'lucide-vue-next';

const props = defineProps<{ event?: ProjectEvent }>();

/** <input type="datetime-local"> wants local time without a zone */
const local = (iso: string | null | undefined) => {
  if (!iso) return '';
  const date = new Date(iso);
  return new Date(date.getTime() - date.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
};

const form = useForm({
  type: (props.event?.type ?? 'onsite') as 'onsite' | 'online',
  title: props.event?.title ?? '',
  description: props.event?.description ?? '',
  location: props.event?.location ?? '',
  start_date: local(props.event?.start_date),
  end_date: local(props.event?.end_date),
  is_published: props.event?.is_published ?? true,
  allows_rsvp: props.event?.allows_rsvp ?? false,
});

const submit = () =>
  form
    .transform((data) => ({
      ...data,
      start_date: data.start_date ? new Date(data.start_date).toISOString() : null,
      end_date: data.end_date ? new Date(data.end_date).toISOString() : null,
    }))
    [props.event ? 'put' : 'post'](
      props.event ? route('manage.events.update', props.event.id) : route('manage.events.store'),
    );

const kind = (active: boolean) =>
  cn(
    'flex min-h-12 flex-1 items-center justify-center gap-2 rounded-xl border-2 font-medium',
    active ? 'border-border-interactive bg-selection/40' : 'border-border',
  );
</script>

<template>
  <form
    class="max-w-2xl space-y-5 rounded-2xl border-2 border-border bg-surface p-5 sm:p-6"
    @submit.prevent="submit"
  >
    <fieldset>
      <legend class="mb-2 text-sm font-medium">{{ _('Where') }}</legend>
      <div class="flex gap-3">
        <button
          type="button"
          :class="kind(form.type === 'onsite')"
          :aria-pressed="form.type === 'onsite'"
          @click="form.type = 'onsite'"
        >
          <MapPin class="size-5" /> {{ _('On site') }}
        </button>
        <button
          type="button"
          :class="kind(form.type === 'online')"
          :aria-pressed="form.type === 'online'"
          @click="form.type = 'online'"
        >
          <Video class="size-5" /> {{ _('Online') }}
        </button>
      </div>
    </fieldset>

    <Field :label="_('Title')" :error="form.errors.title" required v-slot="field">
      <TextInput v-bind="field" name="title" v-model="form.title" autofocus />
    </Field>
    <Field :label="_('Description')" :error="form.errors.description" required v-slot="field">
      <TextArea v-bind="field" name="description" v-model="form.description" />
    </Field>
    <Field
      :label="form.type === 'online' ? _('Link to join') : _('Place')"
      :hint="form.type === 'online' ? _('The video call link, starting with https://') : undefined"
      :error="form.errors.location"
      optional
      v-slot="field"
    >
      <TextInput
        v-bind="field"
        name="location" v-model="form.location"
        :type="form.type === 'online' ? 'url' : 'text'"
      />
    </Field>

    <div class="grid gap-5 sm:grid-cols-2">
      <Field
        :label="_('Starts')"
        :hint="_('Leave empty if not decided yet.')"
        :error="form.errors.start_date"
        optional
        v-slot="field"
      >
        <TextInput v-bind="field" name="start_date" v-model="form.start_date" type="datetime-local" />
      </Field>
      <Field
        :label="_('Ends')"
        :hint="_('Without an end, it lasts an hour.')"
        :error="form.errors.end_date"
        optional
        v-slot="field"
      >
        <TextInput
          v-bind="field"
          name="end_date" v-model="form.end_date"
          type="datetime-local"
          :min="form.start_date || undefined"
        />
      </Field>
    </div>

    <div class="space-y-2">
      <CheckboxField
        name="is_published" v-model="form.is_published"
        :label="_('Published')"
        :hint="_('Drafts are visible to admins only.')"
        :error="form.errors.is_published"
      />
      <CheckboxField
        name="allows_rsvp" v-model="form.allows_rsvp"
        :label="_('Ask people whether they will go')"
        :error="form.errors.allows_rsvp"
      />
    </div>

    <FormActions
      :submit-label="event ? _('Save') : _('Create the event')"
      :processing="form.processing"
      :cancel-href="route('manage.events.index')"
    />
  </form>
</template>
