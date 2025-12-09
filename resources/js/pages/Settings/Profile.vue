<script setup lang="ts">
import { AvatarUpload } from '@/components/avatar';
import Head from '@/components/Head.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import Textarea from '@/components/Textarea.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { currentUser } from '@/composables/useAuth';
import { _ } from '@/composables/useTranslations';
import SettingsLayout from '@/layouts/settings/Layout.vue';

defineProps<{
  updateStatus?: string;
}>();

const profile = currentUser.value!;

const form = useForm({
  email: profile.new_email ?? profile.email,
  firstname: profile.firstname,
  lastname: profile.lastname ?? '',
  legal_id: profile.legal_id ?? '',
  phone: profile.phone ?? '',
  about: profile.about ?? '',
});

const submit = () => form.post(route('profile.update'), { preserveScroll: true });
</script>

<template>
  <Head title="Profile settings" />

  <Breadcrumbs global>
    <Breadcrumb route="profile.edit" text="Settings" no-link />
    <Breadcrumb route="profile.edit" text="Update Profile" />
  </Breadcrumbs>

  <SettingsLayout>
    <form @submit.prevent="submit" class="space-y-8">
      <!-- Grid: 2 cols, 5 rows -->
      <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <!-- Row 1: Title + Avatar -->
        <HeadingSmall
          title="Profile information"
          description="Update your personal information and contact details"
        />
        <div class="justify-self-start md:row-span-2 md:justify-self-end">
          <AvatarUpload
            :subject="profile"
            size="lg"
            upload-route="avatar.update"
            @success="(url: string) => (profile.avatar = url)"
          />
        </div>

        <!-- Row 2: Email (aligned to bottom) -->
        <div class="space-y-2 self-end">
          <Label for="email">
            {{ _('Email address') }}
            <span class="text-text-subtle">{{ profile.new_email ? `(${_('pending verification')})` : '' }}</span>
          </Label>
          <Input id="email" type="email" v-model="form.email" required autocomplete="username" />
          <InputError :message="form.errors.email" />
        </div>

        <!-- Row 3: First Name + Last Name -->
        <div class="space-y-2">
          <Label for="firstname">{{ _('First Name') }}</Label>
          <Input id="firstname" v-model="form.firstname" required />
          <InputError :message="form.errors.firstname" />
        </div>
        <div class="space-y-2">
          <Label for="lastname">{{ _('Last Name') }}</Label>
          <Input id="lastname" v-model="form.lastname" />
          <InputError :message="form.errors.lastname" />
        </div>

        <!-- Row 4: Legal ID + Phone -->
        <div class="space-y-2">
          <Label for="legal_id">{{ _('Legal ID') }}</Label>
          <Input id="legal_id" v-model="form.legal_id" />
          <InputError :message="form.errors.legal_id" />
        </div>
        <div class="space-y-2">
          <Label for="phone">{{ _('Phone Number') }}</Label>
          <Input id="phone" v-model="form.phone" type="tel" />
          <InputError :message="form.errors.phone" />
        </div>

        <!-- Row 5: Bio (full width) -->
        <div class="space-y-2 md:col-span-2">
          <Label for="about">{{ _('About me') }}</Label>
          <Textarea id="about" v-model="form.about" :rows="4" />
          <InputError :message="form.errors.about" />
        </div>
      </div>

      <!-- Email change verification notice -->
      <div v-if="updateStatus === 'email-verification-sent'"
        class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-950/20">
        <p class="text-sm font-medium text-blue-900 dark:text-blue-200">{{ _('Verification email sent') }}</p>
        <p class="mt-2 text-sm text-blue-800 dark:text-blue-300">
          {{ _('A verification link has been sent to your new email address. Please click it to confirm the email change.') }}
        </p>
      </div>

      <!-- Email change completed notice -->
      <div v-if="updateStatus === 'email-verified'"
        class="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-950/20">
        <p class="text-sm font-medium text-green-900 dark:text-green-200">{{ _('Email address verified') }}</p>
        <p class="mt-2 text-sm text-green-800 dark:text-green-300">
          {{ _('Your email address has been successfully updated.') }}
        </p>
      </div>

      <!-- Submit button -->
      <div class="flex items-center gap-4">
        <Button type="submit" :disabled="form.processing" size="lg">{{ _('Save Changes') }}</Button>
      </div>
    </form>
  </SettingsLayout>
</template>
