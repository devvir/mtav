<script setup lang="ts">
import Head from '@/components/Head.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { auth, currentUser } from '@/composables/useAuth';
import { _ } from '@/composables/useTranslations';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { AuthUser } from '@/types/globals';

defineProps<{
  mustVerifyEmail: boolean;
  status?: string;
}>();

const profile = currentUser.value as AuthUser; // TODO : Will this break reactivity anywhere if we don't explicitly update page.auth.user?

const form = useForm({
  email: profile.email,
  firstname: profile.firstname,
  lastname: profile.lastname ?? '',
});

const submit = () => form.patch(route('profile.update'), { preserveScroll: true });
</script>

<template>
  <Head title="Profile settings" />

  <Breadcrumbs global>
    <Breadcrumb route="profile.edit" text="Settings" no-link />
    <Breadcrumb route="profile.edit" text="Update Profile" />
  </Breadcrumbs>

  <SettingsLayout>
    <div class="flex flex-col space-y-6">
      <HeadingSmall title="Profile information" description="Update your name and email address" />

      <form @submit.prevent="submit" class="space-y-6">
        <div class="grid gap-2">
          <Label for="firstname">{{ _('First Name') }}</Label>
          <Input
            id="firstname"
            class="mt-1 block w-full"
            v-model="form.firstname"
            required
            :placeholder="_('First Name')"
          />
          <InputError class="mt-2" :message="form.errors.firstname" />
        </div>
        <div class="grid gap-2">
          <Label for="lastname">{{ _('Last Name') }}</Label>
          <Input id="lastname" class="mt-1 block w-full" v-model="form.lastname" :placeholder="_('Last Name')" />
          <InputError class="mt-2" :message="form.errors.lastname" />
        </div>

        <div class="grid gap-2">
          <Label for="email">{{ _('Email address') }}</Label>
          <Input
            id="email"
            type="email"
            class="mt-1 block w-full"
            v-model="form.email"
            required
            autocomplete="username"
            :placeholder="_('Email address')"
          />
          <InputError class="mt-2" :message="form.errors.email" />
        </div>

        <div v-if="mustVerifyEmail && !auth.verified">
          <p class="-mt-4 text-sm text-muted-foreground">
            {{ _('Your email address is unverified.') }}
            <Link
              :href="route('verification.send')"
              method="post"
              as="button"
              class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
            >
              {{ _('Click here to resend the verification email.') }}
            </Link>
          </p>

          <div v-if="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-600">
            {{ _('A new verification link has been sent to your email address.') }}
          </div>
        </div>

        <div class="flex items-center gap-4">
          <Button :disabled="form.processing">{{ _('Save') }}</Button>

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
