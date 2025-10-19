<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

import Head from '@/components/Head.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { _ } from '@/composables/useTranslations';

const passwordInput = ref<HTMLInputElement | null>(null);
const currentPasswordInput = ref<HTMLInputElement | null>(null);

const form = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const updatePassword = () => {
  form.put(route('password.update'), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
    onError: (errors: Record<string, string>) => {
      if (errors.password) {
        form.reset('password', 'password_confirmation');
        if (passwordInput.value instanceof HTMLInputElement) {
          passwordInput.value.focus();
        }
      }

      if (errors.current_password) {
        form.reset('current_password');
        if (currentPasswordInput.value instanceof HTMLInputElement) {
          currentPasswordInput.value.focus();
        }
      }
    },
  });
};
</script>

<template>
  <Head title="Password settings" />

  <Breadcrumbs global>
    <Breadcrumb route="profile.edit" text="Settings" />
    <Breadcrumb route="password.edit" text="Change Password" />
  </Breadcrumbs>

  <SettingsLayout>
    <div class="space-y-6">
      <HeadingSmall
        title="Update password"
        description="Ensure your account is using a long, random password to stay secure"
      />

      <form @submit.prevent="updatePassword" class="space-y-6">
        <div class="grid gap-2">
          <Label for="current_password">{{ _('Current password') }}</Label>
          <Input
            id="current_password"
            ref="currentPasswordInput"
            v-model="form.current_password"
            type="password"
            class="mt-1 block w-full"
            autocomplete="current-password"
            :placeholder="_('Current password')"
          />
          <InputError :message="form.errors.current_password" />
        </div>

        <div class="grid gap-2">
          <Label for="password">{{ _('New password') }}</Label>
          <Input
            id="password"
            ref="passwordInput"
            v-model="form.password"
            type="password"
            class="mt-1 block w-full"
            autocomplete="new-password"
            :placeholder="_('New password')"
          />
          <InputError :message="form.errors.password" />
        </div>

        <div class="grid gap-2">
          <Label for="password_confirmation">{{ _('Confirm password') }}</Label>
          <Input
            id="password_confirmation"
            v-model="form.password_confirmation"
            type="password"
            class="mt-1 block w-full"
            autocomplete="new-password"
            :placeholder="_('Confirm password')"
          />
          <InputError :message="form.errors.password_confirmation" />
        </div>

        <div class="flex items-center gap-4">
          <Button :disabled="form.processing">{{ _('Save password') }}</Button>

          <Transition
            enter-active-class="transition ease-in-out"
            enter-from-class="opacity-0"
            leave-active-class="transition ease-in-out"
            leave-to-class="opacity-0"
          >
            <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">{{ _('Saved.') }}</p>
          </Transition>
        </div>
      </form>
    </div>
  </SettingsLayout>
</template>
