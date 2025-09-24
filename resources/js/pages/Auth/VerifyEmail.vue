<script setup lang="ts">
import Head from '@/components/Head.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { _ } from '@/composables/useTranslations';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
  status?: string;
}>();

const form = useForm({});

const submit = () => {
  form.post(route('verification.send'));
};
</script>

<template>
  <Head title="Email verification" />

  <AuthLayout
    title="Verify email"
    description="Please verify your email address by clicking on the link we just emailed to you."
  >
    <div v-if="status === 'verification-link-sent'" class="mb-4 text-center text-sm font-medium text-green-600">
      {{ _('A new verification link has been sent to the email address you provided during registration.') }}
    </div>

    <form @submit.prevent="submit" class="space-y-6 text-center">
      <Button :disabled="form.processing" variant="secondary">
        <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
        {{ _('Resend verification email') }}
      </Button>

      <TextLink :href="route('logout')" method="post" as="button" class="mx-auto block text-sm">
        {{ _('Log out') }}
      </TextLink>
    </form>
  </AuthLayout>
</template>
