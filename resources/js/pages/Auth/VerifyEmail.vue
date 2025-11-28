<script setup lang="ts">
import Head from '@/components/Head.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { _ } from '@/composables/useTranslations';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { LoaderCircle } from 'lucide-vue-next';

const form = useForm({});

const submit = () => {
  form.post(route('verification.send'));
};
</script>

<script lang="ts">
export default { layout: null };
</script>

<template>
  <Head title="Email verification" />

  <AuthLayout
    title="Verify email"
    description="Please verify your email address by clicking on the link we just emailed to you."
  >
    <form @submit.prevent="submit" class="space-y-6">
      <Button :disabled="form.processing" variant="secondary" class="w-full">
        <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
        {{ _('Resend verification email') }}
      </Button>
    </form>

    <div class="text-center">
      <TextLink :href="route('logout')" method="post" as="button" class="text-sm">
        {{ _('Log out') }}
      </TextLink>
    </div>
  </AuthLayout>
</template>
