<script setup lang="ts">
import Field from '@/components/form/Field.vue';
import FormActions from '@/components/form/FormActions.vue';
import TextArea from '@/components/form/TextArea.vue';
import TextInput from '@/components/form/TextInput.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import { useForm } from '@inertiajs/vue3';
import { Info } from 'lucide-vue-next';

defineProps<{ limits: Record<string, number> | null }>();

const form = useForm({ name: '', description: '', organization: '' });
</script>

<template>
  <PageHeader
    :title="_('Start a project')"
    :description="
      _('You will be its first admin, and can invite the rest of its people afterwards.')
    "
    :back="{ href: route('projects.index'), label: _('Your projects') }"
  />

  <div class="grid gap-8 lg:grid-cols-[1fr_20rem]">
    <form
      class="space-y-5 rounded-2xl border-2 border-border bg-surface p-5 sm:p-6"
      @submit.prevent="form.post(route('projects.store'))"
    >
      <Field
        :label="_('Name')"
        :hint="_('As people in the project know it.')"
        :error="form.errors.name"
        required
        v-slot="field"
      >
        <TextInput v-bind="field" name="name" v-model="form.name" autofocus />
      </Field>
      <Field :label="_('Description')" :error="form.errors.description" required v-slot="field">
        <TextArea
          v-bind="field"
          name="description" v-model="form.description"
          :placeholder="_('What the project is about, how many dwellings…')"
        />
      </Field>
      <Field
        :label="_('Organization')"
        :hint="_('The federation or institution the project belongs to, if any.')"
        :error="form.errors.organization"
        optional
        v-slot="field"
      >
        <TextInput v-bind="field" name="organization" v-model="form.organization" />
      </Field>
      <FormActions
        :submit-label="_('Start the project')"
        :processing="form.processing"
        :cancel-href="route('projects.index')"
      />
    </form>

    <aside v-if="limits" class="h-fit rounded-2xl bg-info-subtle p-5 text-info-subtle-foreground">
      <Info class="mb-2 size-6" />
      <h2 class="font-semibold">{{ _('Until it is approved') }}</h2>
      <p class="mt-1 text-sm">
        {{
          _(
            'New projects work within some limits, which a superadmin lifts by approving the project. You can ask for approval from the project at any time.',
          )
        }}
      </p>
      <ul class="mt-3 space-y-1 text-sm">
        <li>{{ _('Up to {count} people', { count: limits.members }) }}</li>
        <li>
          {{
            _('Up to {count} families and {units} units', {
              count: limits.families,
              units: limits.units,
            })
          }}
        </li>
        <li>{{ _('Up to {count} invitations a day', { count: limits.invitations_per_day }) }}</li>
        <li>
          {{
            _('Files up to {size} MB, {total} MB in all', {
              size: limits.media_file_mb,
              total: limits.media_storage_mb,
            })
          }}
        </li>
      </ul>
    </aside>
  </div>
</template>
