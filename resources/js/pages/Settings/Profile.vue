<script setup lang="ts">
import PersonAvatar from '@/components/feedback/PersonAvatar.vue';
import Field from '@/components/form/Field.vue';
import FormActions from '@/components/form/FormActions.vue';
import TextArea from '@/components/form/TextArea.vue';
import TextInput from '@/components/form/TextInput.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { Camera } from 'lucide-vue-next';

const props = defineProps<{
  profile: {
    firstname: string;
    lastname: string | null;
    email: string;
    new_email: string | null;
    phone: string | null;
    legal_id: string | null;
    about: string | null;
  };
}>();

const user = computed(() => usePage().props.auth.user!);
const form = useForm({ ...props.profile });

const picker = useTemplateRef<HTMLInputElement>('picker');
const uploadAvatar = (file?: File) =>
  file &&
  router.post(
    route('avatar.update'),
    { avatar: file },
    { forceFormData: true, preserveScroll: true },
  );
</script>

<template>
  <PageHeader :title="_('Profile')" :description="_('How people in your projects see you.')" />

  <div class="mb-6 flex items-center gap-4">
    <PersonAvatar :name="user.name" :src="user.avatar" size="lg" />
    <Button variant="outline" @click="picker?.click()"><Camera /> {{ _('Change picture') }}</Button>
    <input
      ref="picker"
      type="file"
      accept="image/jpeg,image/png,image/webp"
      class="sr-only"
      @change="uploadAvatar(($event.target as HTMLInputElement).files?.[0])"
    />
  </div>
  <p
    v-if="usePage().props.errors.avatar"
    role="alert"
    class="-mt-4 mb-6 text-sm font-medium text-error"
  >
    {{ usePage().props.errors.avatar }}
  </p>

  <form
    class="max-w-2xl space-y-5 rounded-2xl border-2 border-border bg-surface p-5 sm:p-6"
    @submit.prevent="form.patch(route('profile.update'), { preserveScroll: true })"
  >
    <div class="grid gap-5 sm:grid-cols-2">
      <Field :label="_('First name')" :error="form.errors.firstname" required v-slot="field">
        <TextInput v-bind="field" name="firstname" v-model="form.firstname" autocomplete="given-name" />
      </Field>
      <Field :label="_('Last name')" :error="form.errors.lastname" optional v-slot="field">
        <TextInput v-bind="field" name="lastname" v-model="form.lastname" autocomplete="family-name" />
      </Field>
    </div>

    <Field
      :label="_('Email')"
      :hint="
        profile.new_email
          ? _('Waiting for you to confirm {email}.', { email: profile.new_email })
          : _('Changing it sends a confirmation to the new address.')
      "
      :error="form.errors.email"
      required
      v-slot="field"
    >
      <TextInput v-bind="field" name="email" v-model="form.email" type="email" autocomplete="email" />
    </Field>

    <div class="grid gap-5 sm:grid-cols-2">
      <Field :label="_('Phone')" :error="form.errors.phone" optional v-slot="field">
        <TextInput v-bind="field" name="phone" v-model="form.phone" type="tel" autocomplete="tel" />
      </Field>
      <Field
        :label="_('ID number')"
        :hint="_('Only your projects\' admins see it.')"
        :error="form.errors.legal_id"
        optional
        v-slot="field"
      >
        <TextInput v-bind="field" name="legal_id" v-model="form.legal_id" />
      </Field>
    </div>

    <Field :label="_('About you')" :error="form.errors.about" optional v-slot="field">
      <TextArea v-bind="field" name="about" v-model="form.about" />
    </Field>

    <FormActions :submit-label="_('Save')" :processing="form.processing" />
  </form>
</template>
