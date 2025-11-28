<script setup lang="ts">
import Head from '@/components/Head.vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { _ } from '@/composables/useTranslations';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { LoaderCircle } from 'lucide-vue-next';

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => form.post(route('login'), {
  onFinish: () => form.reset('password'),
});
</script>

<script lang="ts">
export default { layout: null };
</script>

<template>
  <Head title="Log in" />

  <AuthLayout
    title="Log in to your account"
    description="Enter your email and password below to log in"
  >
    <!-- Login form -->
    <form @submit.prevent="submit" class="space-y-6">
      <div class="space-y-5">
        <!-- Email field -->
        <div class="space-y-2">
          <Label for="email" class="text-base">{{ _('Email address') }}</Label>
          <Input
            id="email"
            type="email"
            required
            autofocus
            :tabindex="1"
            autocomplete="email"
            v-model="form.email"
            :placeholder="_('email@example.com')"
            class="h-12 text-base"
          />
          <InputError :message="form.errors.email" />
        </div>

        <!-- Password field -->
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <Label for="password" class="text-base">{{ _('Password') }}</Label>
            <TextLink
              :href="route('password.request')"
              class="text-sm hover:underline"
              :tabindex="5"
            >
              {{ _('Forgot Password?') }}
            </TextLink>
          </div>
          <Input
            id="password"
            type="password"
            required
            :tabindex="2"
            autocomplete="current-password"
            v-model="form.password"
            :placeholder="_('Password')"
            class="h-12 text-base"
          />
          <InputError :message="form.errors.password" />
        </div>

        <!-- Remember me -->
        <div class="flex items-center">
          <Label for="remember" class="flex cursor-pointer items-center gap-3">
            <Checkbox id="remember" v-model="form.remember" :tabindex="3" class="h-5 w-5" />
            <span class="text-base">{{ _('Remember me') }}</span>
          </Label>
        </div>
      </div>

      <!-- Submit button -->
      <Button
        type="submit"
        class="h-12 w-full text-base font-semibold"
        :tabindex="4"
        :disabled="form.processing"
      >
        <LoaderCircle v-if="form.processing" class="mr-2 h-5 w-5 animate-spin" />
        {{ _('Log in') }}
      </Button>
    </form>
  </AuthLayout>
</template>
