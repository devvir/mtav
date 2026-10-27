<script setup lang="ts">
import Badge from '@/components/feedback/Badge.vue';
import DateTime from '@/components/feedback/DateTime.vue';
import CheckboxField from '@/components/form/CheckboxField.vue';
import Field from '@/components/form/Field.vue';
import SelectInput from '@/components/form/SelectInput.vue';
import TextInput from '@/components/form/TextInput.vue';
import InfoTip from '@/components/help/InfoTip.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { confirm } from '@/composables/useDialogs';
import { useProjectChanges } from '@/composables/useRealtime';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import type { Invitation, Ref } from '@/types/models';
import { router, useForm } from '@inertiajs/vue3';
import { Send, X } from 'lucide-vue-next';

const props = defineProps<{
  invitations: Invitation[];
  families: Ref[];
  limits: { members: number; invitations_per_day: number } | null;
}>();

const preselected = Number(new URLSearchParams(window.location.search).get('family')) || null;

const form = useForm({
  email: '',
  firstname: '',
  lastname: '',
  family_id: preselected ?? props.families[0]?.id ?? null,
  is_admin: false,
});

const send = () =>
  form.post(route('manage.invitations.store'), {
    preserveScroll: true,
    onSuccess: () => form.reset('email', 'firstname', 'lastname'),
  });

async function withdraw(invitation: Invitation) {
  if (
    await confirm({
      title: _('Withdraw the invitation to {email}?', { email: invitation.email }),
      confirmLabel: _('Withdraw'),
      danger: true,
    })
  ) {
    router.delete(route('manage.invitations.destroy', invitation.id), { preserveScroll: true });
  }
}

useProjectChanges({ invitations: ['invitations'], families: ['families'] });
</script>

<template>
  <PageHeader
    :title="_('Invitations')"
    :description="_('People join the project from the link they receive by email.')"
  >
    <template #info>
      <InfoTip :title="_('How invitations work')" tour="manage.invitations">
        <p>
          {{
            _(
              'People join only by invitation: an emailed link, valid for two weeks. Members can also invite people into their own family.',
            )
          }}
        </p>
      </InfoTip>
    </template>
  </PageHeader>

  <div class="grid gap-6 lg:grid-cols-[1fr_1fr]">
    <form
      novalidate
      data-tour="invite.form"
      class="h-fit space-y-5 rounded-2xl border-2 border-border bg-surface p-5 sm:p-6"
      @submit.prevent="send"
    >
      <h2 class="text-lg font-semibold">{{ _('Invite someone') }}</h2>
      <Field
        data-tour="invite.email"
        :label="_('Email')"
        :error="form.errors.email"
        required
        v-slot="field"
      >
        <TextInput
          v-bind="field"
          name="email"
          v-model="form.email"
          type="email"
          autocomplete="off"
        />
      </Field>
      <div class="grid gap-5 sm:grid-cols-2">
        <Field :label="_('First name')" :error="form.errors.firstname" optional v-slot="field">
          <TextInput v-bind="field" name="firstname" v-model="form.firstname" autocomplete="off" />
        </Field>
        <Field :label="_('Last name')" :error="form.errors.lastname" optional v-slot="field">
          <TextInput v-bind="field" name="lastname" v-model="form.lastname" autocomplete="off" />
        </Field>
      </div>
      <Field
        data-tour="invite.family"
        :label="_('Family')"
        :hint="form.is_admin ? _('Optional for admins.') : undefined"
        :error="form.errors.family_id"
        :required="!form.is_admin"
        v-slot="field"
      >
        <SelectInput
          v-bind="field"
          name="family_id"
          v-model="form.family_id"
          :options="[
            ...(form.is_admin ? [{ value: '', label: _('No family') }] : []),
            ...families.map((f) => ({ value: f.id, label: f.name })),
          ]"
          :placeholder="families.length ? undefined : _('No families yet')"
        />
      </Field>
      <CheckboxField
        data-tour="invite.admin"
        name="is_admin"
        v-model="form.is_admin"
        :label="_('As an admin')"
        :hint="_('To help configure the project and run the lottery.')"
      />
      <p v-if="limits" class="text-sm text-text-muted">
        {{
          _('Until the project is approved: up to {members} people, {day} invitations a day.', {
            members: limits.members,
            day: limits.invitations_per_day,
          })
        }}
      </p>
      <div class="flex justify-end">
        <Button type="submit" :disabled="form.processing"
          ><Send /> {{ _('Send the invitation') }}</Button
        >
      </div>
    </form>

    <section data-tour="invite.pending">
      <h2 class="mb-3 text-lg font-semibold">
        {{ _('Waiting for an answer ({count})', { count: invitations.length }) }}
      </h2>
      <ul
        v-if="invitations.length"
        class="divide-y divide-border-subtle rounded-2xl border-2 border-border bg-surface"
      >
        <li
          v-for="invitation in invitations"
          :key="invitation.id"
          class="flex items-center gap-3 px-4 py-3"
        >
          <div class="min-w-0 flex-1">
            <p class="flex flex-wrap items-center gap-2 font-medium">
              <span class="truncate">{{ invitation.name ?? invitation.email }}</span>
              <Badge v-if="invitation.is_admin" tone="info">{{ _('Admin') }}</Badge>
            </p>
            <p class="truncate text-sm text-text-muted">
              {{ invitation.family?.name ?? '' }}{{ invitation.family ? ' · ' : ''
              }}{{ _('Until') }} <DateTime :value="invitation.expires_at" :time="false" />
              <template v-if="invitation.inviter">
                · {{ _('by {name}', { name: invitation.inviter }) }}</template
              >
            </p>
          </div>
          <Button
            variant="ghost"
            size="icon"
            :aria-label="_('Withdraw')"
            @click="withdraw(invitation)"
            ><X
          /></Button>
        </li>
      </ul>
      <p
        v-else
        class="rounded-2xl border-2 border-dashed border-border p-6 text-center text-text-muted"
      >
        {{ _('No pending invitations.') }}
      </p>
    </section>
  </div>
</template>
