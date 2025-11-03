<script setup lang="ts">
// Copilot - pending review
import Head from '@/components/Head.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { _ } from '@/composables/useTranslations';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { LoaderCircle } from 'lucide-vue-next';
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

const props = defineProps<{
  user: Admin | Member;
  token: string;
}>();

const form = useForm({
  email: props.user.email,
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
  form.post(route('invitation.store'), {
    onSuccess: () => {
      form.reset('password', 'password_confirmation');
    },
  });
};

const isAdmin = props.user.is_admin;

// Type guards for family and project
const hasFamilyData = (f: any): f is ApiResource<Family> => f && 'name' in f;
const hasProjectData = (p: any): p is ApiResource<Project> => p && 'name' in p;

const memberFamily = !isAdmin ? (props.user as Member).family : undefined;
const family = hasFamilyData(memberFamily) ? memberFamily : undefined;

const familyProject = family && hasProjectData(family.project) ? family.project : undefined;
const project = familyProject || (props.user.projects && props.user.projects.length > 0 ? props.user.projects[0] : undefined);
</script>

<script lang="ts">
export default {
  layout: null,
};
</script>

<template>

    <Head :title="_('Complete Registration')" />

    <AuthLayout>
        <!-- Redesigned Header Section with Better Hierarchy -->
        <div class="mb-6 space-y-4 md:mb-8 md:space-y-6">
            <!-- Title - Larger, More Prominent with User's Name -->
            <h1 class="text-center text-xl font-semibold md:text-2xl lg:text-3xl">
                <span class="inline-block">{{ _('Welcome') }}, {{ user.firstname || user.name.split(' ')[0] }}!</span>
                <br class="md:hidden" />
                <span class="inline-block text-base font-normal md:ml-2 md:text-2xl md:font-semibold lg:text-3xl">{{
                    _('Please complete your registration') }}</span>
            </h1>

            <!-- Account Type Badge - Full Width, More Prominent -->
            <div class="flex items-center justify-center">
                <div
                    class="w-full rounded-lg bg-purple-100 px-4 py-3 text-center text-sm font-medium text-purple-700 dark:bg-purple-900/30 dark:text-purple-300 md:text-base">
                    {{ isAdmin ? _('Administrator Account') : _('Member Account') }}
                </div>
            </div>

            <!-- Email and Project(s) in a Card - Side by Side on Large Screens -->
            <div class="rounded-lg border bg-card p-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <!-- Email -->
                    <div class="space-y-1 text-center">
                        <div class="text-xs font-medium uppercase tracking-wide text-text-muted">
                            {{ _('Email') }}
                        </div>
                        <div class="text-sm font-medium text-text">
                            {{ user.email }}
                        </div>
                    </div>

                    <!-- Project(s) -->
                    <div v-if="isAdmin && user.projects?.length" class="space-y-1 text-center">
                        <div class="text-xs font-medium uppercase tracking-wide text-text-muted">
                            {{ user.projects.length === 1 ? _('Project') : _('Projects') }}
                        </div>
                        <div class="text-sm font-medium text-text">
                            {{ user.projects.map((p) => p.name).join(', ') }}
                        </div>
                    </div>
                    <div v-else-if="project" class="space-y-1 text-center">
                        <div class="text-xs font-medium uppercase tracking-wide text-text-muted">
                            {{ _('Project') }}
                        </div>
                        <div class="text-sm font-medium text-text">
                            {{ project.name }}
                        </div>
                    </div>

                    <!-- Family (for Members) - Takes Full Width if Present -->
                    <div v-if="family && !isAdmin" class="space-y-1 text-center md:col-span-2">
                        <div class="text-xs font-medium uppercase tracking-wide text-text-muted">
                            {{ _('Family') }}
                        </div>
                        <div class="text-sm font-medium text-text">
                            {{ family.name }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Description/Explanation - Closer to Form -->
            <p class="text-center text-base text-text-muted">
                {{ isAdmin
                ? _('Please set your password to access your administrator account.')
                : _('Please set your password to complete your registration.')
                }}
            </p>
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <!-- Two-column grid on large screens -->
            <div class="grid gap-6 md:grid-cols-2">
                <!-- Firstname -->
                <div class="grid gap-2">
                    <Label for="firstname">{{ _('First Name') }}</Label>
                    <Input id="firstname" type="text" v-model="form.firstname" :placeholder="_('Your first name')" />
                    <InputError :message="form.errors.firstname" />
                </div>

                <!-- Lastname -->
                <div class="grid gap-2">
                    <Label for="lastname">{{ _('Last Name') }}</Label>
                    <Input id="lastname" type="text" v-model="form.lastname" :placeholder="_('Your last name')" />
                    <InputError :message="form.errors.lastname" />
                </div>

                <!-- Phone -->
                <div class="grid gap-2">
                    <Label for="phone">{{ _('Phone') }}</Label>
                    <Input id="phone" type="tel" v-model="form.phone" :placeholder="_('Your phone number')" />
                    <InputError :message="form.errors.phone" />
                </div>

                <!-- Legal ID -->
                <div class="grid gap-2">
                    <Label for="legal_id">{{ _('Legal ID') }}</Label>
                    <Input id="legal_id" type="text" autocomplete="off" v-model="form.legal_id"
                        :placeholder="_('Your legal identification number')" />
                    <InputError :message="form.errors.legal_id" />
                </div>

                <!-- Password -->
                <div class="grid gap-2">
                    <Label for="password">{{ _('Password') }}</Label>
                    <Input id="password" type="password" autocomplete="new-password" required v-model="form.password"
                        :placeholder="_('Enter a secure password')" />
                    <InputError :message="form.errors.password" />
                </div>

                <!-- Password Confirmation -->
                <div class="grid gap-2">
                    <Label for="password_confirmation">{{ _('Confirm Password') }}</Label>
                    <Input id="password_confirmation" type="password" autocomplete="new-password" required
                        v-model="form.password_confirmation" :placeholder="_('Re-enter your password')" />
                    <InputError :message="form.errors.password_confirmation" />
                </div>
            </div>

            <!-- Avatar Upload (full width, TODO: add preview) -->
            <div class="grid gap-2">
                <Label for="avatar">{{ _('Profile Picture') }}</Label>
                <Input id="avatar" type="file" accept="image/*"
                    @change="(e: Event) => form.avatar = ((e.target as HTMLInputElement).files?.[0] || null)" />
                <p class="text-xs text-text-muted">{{ _('Optional - Upload a profile photo') }}</p>
                <!-- TODO: Add image preview -->
                <InputError :message="form.errors.avatar" />
            </div>

            <!-- Elastic Spacing Before Submit Button -->
            <div class="mt-2 md:mt-6"></div>

            <!-- Submit Button -->
            <Button type="submit" :disabled="form.processing" class="w-full">
                <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                {{ _('Complete Registration') }}
            </Button>
        </form>

        <!-- Login Link -->
        <div class="mt-6 text-center">
            <a :href="route('login')" class="text-sm text-primary hover:underline">
                {{ _('Looking to log in instead?') }}
            </a>
        </div>
    </AuthLayout>
</template>
