<script setup lang="ts">
import Badge from '@/components/feedback/Badge.vue';
import Pagination from '@/components/feedback/Pagination.vue';
import PersonAvatar from '@/components/feedback/PersonAvatar.vue';
import SearchField from '@/components/feedback/SearchField.vue';
import CheckboxField from '@/components/form/CheckboxField.vue';
import Field from '@/components/form/Field.vue';
import SelectInput from '@/components/form/SelectInput.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import Modal from '@/components/modal/Modal.vue';
import { Button } from '@/components/ui/button';
import { confirm } from '@/composables/useDialogs';
import { useProjectChanges } from '@/composables/useRealtime';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import type { Paginated, Person, Ref } from '@/types/models';
import { Link, router, useForm } from '@inertiajs/vue3';
import { Mail, Pencil, UserMinus } from 'lucide-vue-next';

defineProps<{ members: Paginated<Person>; families: Ref[]; filters: { q: string | null } }>();

const editing = ref<Person | null>(null);
const open = ref(false);
const form = useForm({ family_id: null as number | null, is_admin: false });

function edit(person: Person) {
  editing.value = person;
  form.defaults({ family_id: person.family?.id ?? null, is_admin: person.is_admin }).reset();
  form.clearErrors();
  open.value = true;
}

/** e.g. "a project needs at least one admin" */
const roleError = computed(() => (form.errors as Record<string, string>).member);

const save = () =>
  form.put(route('manage.people.update', editing.value!.id), {
    preserveScroll: true,
    onSuccess: () => (open.value = false),
  });

async function remove(person: Person) {
  if (
    await confirm({
      title: _('Remove {name} from the project?', { name: person.name }),
      message: _('Their account stays; they just stop taking part in this project.'),
      confirmLabel: _('Remove'),
      danger: true,
    })
  ) {
    router.delete(route('manage.people.destroy', person.id), { preserveScroll: true });
  }
}

useProjectChanges({ people: ['members'] });
</script>

<template>
  <PageHeader
    :title="_('People')"
    :description="_('Who takes part in the project, in which family, and who manages it.')"
  >
    <template #actions>
      <Button as-child
        ><Link :href="route('manage.invitations.index')"
          ><Mail /> {{ _('Invite people') }}</Link
        ></Button
      >
    </template>
  </PageHeader>

  <SearchField
    :model-value="filters.q"
    :placeholder="_('Search by name or email')"
    class="mb-4 max-w-md"
  />

  <ul class="divide-y divide-border-subtle rounded-2xl border-2 border-border bg-surface">
    <li v-for="person in members.data" :key="person.id" class="flex items-center gap-3 px-4 py-3">
      <PersonAvatar :name="person.name" :src="person.avatar" />
      <div class="min-w-0 flex-1">
        <p class="flex flex-wrap items-center gap-2 font-semibold">
          {{ person.name }} <Badge v-if="person.is_admin" tone="info">{{ _('Admin') }}</Badge>
        </p>
        <p class="truncate text-sm text-text-muted">
          {{ person.family?.name ?? _('No family') }} · {{ person.email }}
        </p>
      </div>
      <Button
        variant="ghost"
        size="icon"
        :aria-label="_('Change role or family of {name}', { name: person.name })"
        @click="edit(person)"
        ><Pencil
      /></Button>
      <Button
        variant="ghost"
        size="icon"
        :aria-label="_('Remove {name}', { name: person.name })"
        @click="remove(person)"
        ><UserMinus
      /></Button>
    </li>
  </ul>

  <Pagination :page="members" />

  <Modal
    v-if="editing"
    v-model:open="open"
    :title="editing.name"
    :description="_('Their role and family in this project.')"
    persistent
  >
    <form id="person-form" class="space-y-5" @submit.prevent="save">
      <Field
        :label="_('Family')"
        :hint="_('Someone who is not an admin must be in a family.')"
        :error="form.errors.family_id"
        v-slot="field"
      >
        <SelectInput
          v-bind="field"
          name="family_id" v-model="form.family_id"
          :options="[
            { value: '', label: _('No family') },
            ...families.map((f) => ({ value: f.id, label: f.name })),
          ]"
        />
      </Field>
      <CheckboxField
        name="is_admin" v-model="form.is_admin"
        :label="_('Admin of the project')"
        :hint="_('Admins configure the project and run the lottery.')"
        :error="form.errors.is_admin"
      />
      <p v-if="roleError" role="alert" class="text-sm font-medium text-error">{{ roleError }}</p>
    </form>
    <template #footer="{ close }">
      <Button variant="outline" @click="close">{{ _('Cancel') }}</Button>
      <Button type="submit" form="person-form" :disabled="form.processing">{{ _('Save') }}</Button>
    </template>
  </Modal>
</template>
