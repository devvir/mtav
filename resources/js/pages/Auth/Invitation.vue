<script setup lang="ts">
import Field from '@/components/form/Field.vue';
import TextInput from '@/components/form/TextInput.vue';
import { Button } from '@/components/ui/button';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { PartyPopper } from 'lucide-vue-next';

/**
 * Where an invitation link lands: join with a new account, join as the signed-in
 * person, or learn that the invitation is meant for someone else.
 */
const props = defineProps<{
  token: string;
  invitation: {
    email: string;
    firstname: string | null;
    lastname: string | null;
    is_admin: boolean;
    project: string;
    family: string | null;
    inviter: string | null;
  };
  mismatch: boolean;
}>();

const user = computed(() => usePage().props.auth.user);

const form = useForm({
  firstname: props.invitation.firstname ?? '',
  lastname: props.invitation.lastname ?? '',
  password: '',
  password_confirmation: '',
});

const accept = () => form.post(route('invitation.accept', props.token));

const what = computed(() => {
  const { is_admin, family, project } = props.invitation;
  if (is_admin && family)
    return _('to help manage {project}, as a member of the {family} family', { project, family });
  if (is_admin) return _('to help manage {project}', { project });
  return _('to join the {family} family in {project}', { project, family: family ?? '' });
});
</script>

<template>
  <Head :title="_('Invitation to {project}', { project: invitation.project })" />
  <PartyPopper class="mb-4 size-12 text-interactive" />
  <h1 class="text-2xl font-bold">{{ _('You are invited') }}</h1>
  <p class="mt-2 text-text-muted">
    {{
      invitation.inviter
        ? _('{inviter} invites you {what}.', { inviter: invitation.inviter, what })
        : _('You are invited {what}.', { what })
    }}
  </p>

  <template v-if="mismatch">
    <p class="mt-6 rounded-lg bg-warning-subtle p-4 text-warning-subtle-foreground">
      {{
        _('This invitation is for {email}, but you are signed in as {current}.', {
          email: invitation.email,
          current: user?.email ?? '',
        })
      }}
    </p>
    <Button class="mt-6 w-full" variant="outline" @click="router.post(route('logout'))">{{
      _('Sign out to accept it')
    }}</Button>
  </template>

  <Button v-else-if="user" class="mt-6 w-full" :disabled="form.processing" @click="accept">
    {{ _('Join {project}', { project: invitation.project }) }}
  </Button>

  <form v-else class="mt-6 space-y-5" @submit.prevent="accept">
    <p class="text-sm text-text-muted">
      {{ _('Choose a password for your account ({email}).', { email: invitation.email }) }}
    </p>

    <div class="grid gap-5 sm:grid-cols-2">
      <Field :label="_('First name')" :error="form.errors.firstname" required v-slot="field">
        <TextInput v-bind="field" name="firstname" v-model="form.firstname" autocomplete="given-name" />
      </Field>
      <Field :label="_('Last name')" :error="form.errors.lastname" optional v-slot="field">
        <TextInput v-bind="field" name="lastname" v-model="form.lastname" autocomplete="family-name" />
      </Field>
    </div>

    <Field
      :label="_('Password')"
      :hint="_('At least 8 characters.')"
      :error="form.errors.password"
      required
      v-slot="field"
    >
      <TextInput
        v-bind="field"
        name="password" v-model="form.password"
        type="password"
        autocomplete="new-password"
      />
    </Field>
    <Field
      :label="_('Repeat the password')"
      :error="form.errors.password_confirmation"
      required
      v-slot="field"
    >
      <TextInput
        v-bind="field"
        name="password_confirmation" v-model="form.password_confirmation"
        type="password"
        autocomplete="new-password"
      />
    </Field>

    <Button type="submit" class="w-full" :disabled="form.processing">{{
      _('Join {project}', { project: invitation.project })
    }}</Button>
  </form>
</template>
