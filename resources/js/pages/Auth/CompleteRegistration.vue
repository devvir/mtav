<script setup lang="ts">
// Copilot - pending review
import Head from '@/components/Head.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { _ } from '@/composables/useTranslations';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { LoaderCircle } from 'lucide-vue-next';

const props = defineProps<{
  user: any;
  email: string;
  token: string;
}>();

const form = useForm({
  email: props.email,
  token: props.token,
  password: '',
  password_confirmation: '',
  firstname: props.user.firstname || '',
  lastname: props.user.lastname || '',
  phone: props.user.phone || '',
  legal_id: props.user.legal_id || '',
  avatar: null as File | null,
});

const submit = () => {
  form.post(route('invitation.complete'), {
    onSuccess: () => {
      form.reset('password', 'password_confirmation');
    },
  });
};

const isAdmin = props.user.is_admin;
const family = props.user.asMember?.family;
const project = family?.project || props.user.projects?.[0];
</script>

<script lang="ts">
export default {
  layout: null,
};
</script>

<template>
  <Head :title="_('Complete Registration')" />

  <AuthLayout
    :title="_('Complete Your Registration')"
    :description="
      isAdmin
        ? _('Welcome! Please set your password to access your administrator account.')
        : _('Welcome! Please set your password to complete your registration.')
    "
  >
    <div class="mb-6 space-y-3">
      <div v-if="isAdmin" class="flex items-center justify-center gap-2">
        <Badge variant="secondary" class="bg-purple-100 text-purple-700">
          {{ _('Administrator Account') }}
        </Badge>
      </div>

      <div v-if="project" class="text-center">
        <p class="text-sm text-text-muted">
          {{ _('Project') }}: <strong>{{ project.name }}</strong>
        </p>
      </div>

      <div v-if="family && !isAdmin" class="text-center">
        <p class="text-sm text-text-muted">
          {{ _('Family') }}: <strong>{{ family.name }}</strong>
        </p>
      </div>
    </div>

    <form @submit.prevent="submit" class="flex flex-col gap-6">
      <div class="grid gap-6">
        <!-- Email (read-only) -->
        <div class="grid gap-2">
          <Label for="email">{{ _('Email address') }}</Label>
          <Input id="email" type="email" :value="user.email" disabled />
          <p class="text-xs text-text-muted">{{ _('This cannot be changed') }}</p>
        </div>

        <!-- Password -->
        <div class="grid gap-2">
          <Label for="password">{{ _('Password') }}</Label>
          <Input
            id="password"
            type="password"
            required
            v-model="form.password"
            :placeholder="_('Enter a secure password')"
          />
          <InputError :message="form.errors.password" />
        </div>

        <!-- Password Confirmation -->
        <div class="grid gap-2">
          <Label for="password_confirmation">{{ _('Confirm Password') }}</Label>
          <Input
            id="password_confirmation"
            type="password"
            required
            v-model="form.password_confirmation"
            :placeholder="_('Re-enter your password')"
          />
          <InputError :message="form.errors.password_confirmation" />
        </div>

        <!-- Firstname -->
        <div class="grid gap-2">
          <Label for="firstname">{{ _('First Name') }}</Label>
          <Input
            id="firstname"
            type="text"
            v-model="form.firstname"
            :placeholder="_('Your first name')"
          />
          <InputError :message="form.errors.firstname" />
        </div>

        <!-- Lastname -->
        <div class="grid gap-2">
          <Label for="lastname">{{ _('Last Name') }}</Label>
          <Input
            id="lastname"
            type="text"
            v-model="form.lastname"
            :placeholder="_('Your last name')"
          />
          <InputError :message="form.errors.lastname" />
        </div>

        <!-- Phone -->
        <div class="grid gap-2">
          <Label for="phone">{{ _('Phone') }}</Label>
          <Input
            id="phone"
            type="tel"
            v-model="form.phone"
            :placeholder="_('Your phone number')"
          />
          <InputError :message="form.errors.phone" />
        </div>

        <!-- Legal ID -->
        <div class="grid gap-2">
          <Label for="legal_id">{{ _('Legal ID') }}</Label>
          <Input
            id="legal_id"
            type="text"
            v-model="form.legal_id"
            :placeholder="_('Your legal identification number')"
          />
          <InputError :message="form.errors.legal_id" />
        </div>

        <!-- Avatar Upload -->
        <div class="grid gap-2">
          <Label for="avatar">{{ _('Profile Picture') }}</Label>
          <Input
            id="avatar"
            type="file"
            accept="image/*"
            @change="(e) => form.avatar = (e.target as HTMLInputElement).files?.[0] || null"
          />
          <p class="text-xs text-text-muted">{{ _('Optional - Upload a profile photo') }}</p>
          <InputError :message="form.errors.avatar" />
        </div>

        <Button type="submit" :disabled="form.processing" class="w-full">
          <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
          {{ _('Complete Registration') }}
        </Button>
      </div>
    </form>
  </AuthLayout>
</template>
