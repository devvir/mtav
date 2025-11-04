<script setup lang="ts">
import AvatarUpload from '@/components/AvatarUpload.vue';
import Head from '@/components/Head.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import Textarea from '@/components/Textarea.vue';
import { currentUser } from '@/composables/useAuth';
import { _ } from '@/composables/useTranslations';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { AuthUser } from '@/types/globals';

defineProps<{
  mustVerifyEmail: boolean;
  status?: string;
}>();

const profile = currentUser.value as AuthUser;

const form = useForm({
  email: profile.email,
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
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Row 1: Title + Avatar -->
                <HeadingSmall title="Profile information" description="Update your personal information and contact details" />
                <div class="md:row-span-2 justify-self-start md:justify-self-end">
                    <AvatarUpload
                        :subject="profile"
                        size="lg"
                        upload-route="avatar.update"
                        @success="(url) => profile.avatar = url"
                    />
                </div>

                <!-- Row 2: Email (aligned to bottom) -->
                <div class="space-y-2 self-end">
                    <Label for="email">{{ _('Email address') }}</Label>
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

            <!-- Email verification notice -->
            <div v-if="mustVerifyEmail && !profile.is_verified" class="rounded-lg bg-amber-50 dark:bg-amber-950/20 p-4 border border-amber-200 dark:border-amber-800">
                <p class="text-sm text-amber-900 dark:text-amber-200">
                    {{ _('Your email address is unverified.') }}
                    <Link :href="route('verification.send')" method="post" as="button" class="font-medium underline underline-offset-4 hover:no-underline">
                        {{ _('Click here to resend the verification email.') }}
                    </Link>
                </p>
                <div v-if="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-700 dark:text-green-400">
                    {{ _('A new verification link has been sent to your email address.') }}
                </div>
            </div>

            <!-- Submit button -->
            <div class="flex items-center gap-4">
                <Button type="submit" :disabled="form.processing" size="lg">{{ _('Save changes') }}</Button>
            </div>
        </form>
    </SettingsLayout>
</template>
