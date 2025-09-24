<script setup lang="ts">
import Head from '@/components/Head.vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { _ } from '@/composables/useTranslations';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
  status?: string;
}>();

const form = useForm({
  email: '',
});

const submit = () => {
  form.post(route('password.email'));
};
</script>

<template>
  <Head title="Forgot password" />

  <AuthLayout title="Forgot password" description="Enter your email to receive a password reset link">
    <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
      {{ status }}
    </div>

    <div class="space-y-6">
      <form @submit.prevent="submit">
        <div class="grid gap-2">
          <Label for="email">{{ _('Email address') }}</Label>
          <Input
            id="email"
            type="email"
            name="email"
            autocomplete="off"
            v-model="form.email"
            autofocus
            placeholder="email@example.com"
          />
          <InputError :message="form.errors.email" />
        </div>

        <div class="my-6 flex items-center justify-start">
          <Button class="w-full" :disabled="form.processing">
            <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
            {{ _('Email password reset link') }}
          </Button>
        </div>
      </form>

      <div class="space-x-1 text-center text-sm text-muted-foreground">
        <span>{{ _('Or, return to') }}</span>
        <TextLink :href="route('login')">{{ _('Log in') }}</TextLink>
      </div>
    </div>
  </AuthLayout>
</template>
