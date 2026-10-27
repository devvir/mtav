<script setup lang="ts">
import DateTime from '@/components/feedback/DateTime.vue';
import Field from '@/components/form/Field.vue';
import TextInput from '@/components/form/TextInput.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { confirm } from '@/composables/useDialogs';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import type { Invitation } from '@/types/models';
import { router, useForm } from '@inertiajs/vue3';
import { Send, X } from 'lucide-vue-next';

/**
 * Members bring the rest of their family in.
 */
const props = defineProps<{ family: { id: number; name: string }; invitations: Invitation[] }>();

const form = useForm({ email: '', firstname: '', lastname: '' });

const send = () =>
  form.post(route('invitations.store'), { preserveScroll: true, onSuccess: () => form.reset() });

async function withdraw(invitation: Invitation) {
  if (
    await confirm({
      title: _('Withdraw the invitation to {email}?', { email: invitation.email }),
      confirmLabel: _('Withdraw'),
      danger: true,
    })
  ) {
    router.delete(route('invitations.destroy', invitation.id), { preserveScroll: true });
  }
}
</script>

<template>
  <PageHeader
    :title="_('Invite to your family')"
    :description="
      _(
        'Invite the people of the {family} family to the project. They receive an email with a link.',
        { family: props.family.name },
      )
    "
  />

  <form
    novalidate
    class="max-w-2xl space-y-5 rounded-2xl border-2 border-border bg-surface p-5 sm:p-6"
    @submit.prevent="send"
  >
    <Field :label="_('Their email')" :error="form.errors.email" required v-slot="field">
      <TextInput v-bind="field" name="email" v-model="form.email" type="email" autocomplete="off" />
    </Field>
    <div class="grid gap-5 sm:grid-cols-2">
      <Field
        :label="_('First name')"
        :hint="_('To greet them in the email.')"
        :error="form.errors.firstname"
        optional
        v-slot="field"
      >
        <TextInput v-bind="field" name="firstname" v-model="form.firstname" autocomplete="off" />
      </Field>
      <Field :label="_('Last name')" :error="form.errors.lastname" optional v-slot="field">
        <TextInput v-bind="field" name="lastname" v-model="form.lastname" autocomplete="off" />
      </Field>
    </div>
    <div class="flex justify-end">
      <Button type="submit" :disabled="form.processing"
        ><Send /> {{ _('Send the invitation') }}</Button
      >
    </div>
  </form>

  <section v-if="invitations.length" class="mt-8 max-w-2xl">
    <h2 class="mb-3 text-lg font-semibold">{{ _('Waiting for an answer') }}</h2>
    <ul class="divide-y divide-border-subtle rounded-2xl border-2 border-border bg-surface">
      <li
        v-for="invitation in invitations"
        :key="invitation.id"
        class="flex items-center gap-3 px-4 py-3"
      >
        <span class="min-w-0 flex-1">
          <span class="block truncate font-medium">{{ invitation.name ?? invitation.email }}</span>
          <span class="block text-sm text-text-muted">
            {{ invitation.name ? invitation.email + ' · ' : '' }}{{ _('Until') }}
            <DateTime :value="invitation.expires_at" :time="false" />
          </span>
        </span>
        <Button
          v-if="invitation.can.delete"
          variant="ghost"
          size="icon"
          :aria-label="_('Withdraw')"
          @click="withdraw(invitation)"
          ><X
        /></Button>
      </li>
    </ul>
  </section>
</template>
