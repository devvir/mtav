<script setup lang="ts">
import Head from '@/components/Head.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { _ } from '@/composables/useTranslations';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { LoaderCircle } from 'lucide-vue-next';

interface Props {
  token: string;
  email: string;
}

const props = defineProps<Props>();

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
});

const submit = () =>
  form.post(route('password.store'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
</script>

<script lang="ts">
export default { layout: null };
</script>

<template>
  <Head title="Reset Password" />

  <AuthLayout title="Reset Password" description="Please enter your new password below">
    <form @submit.prevent="submit" class="space-y-6">
      <div class="grid gap-2">
        <Label for="email">{{ _('Email') }}</Label>
        <Input
          id="email"
          type="email"
          name="email"
          autocomplete="email"
          v-model="form.email"
          class="mt-1 block w-full"
          readonly
        />
        <InputError :message="form.errors.email" class="mt-2" />
      </div>

      <div class="grid gap-2">
        <Label for="password">{{ _('Password') }}</Label>
        <Input
          id="password"
          type="password"
          name="password"
          autocomplete="new-password"
          v-model="form.password"
          class="mt-1 block w-full"
          autofocus
          :placeholder="_('Password')"
        />
        <InputError :message="form.errors.password" />
      </div>

      <div class="grid gap-2">
        <Label for="password_confirmation">{{ _('Confirm Password') }}</Label>
        <Input
          id="password_confirmation"
          type="password"
          name="password_confirmation"
          autocomplete="new-password"
          v-model="form.password_confirmation"
          class="mt-1 block w-full"
          :placeholder="_('Confirm Password')"
        />
        <InputError :message="form.errors.password_confirmation" />
      </div>

      <Button type="submit" class="w-full" :disabled="form.processing">
        <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
        {{ _('Reset Password') }}
      </Button>
    </form>
  </AuthLayout>
</template>
