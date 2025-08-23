<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type User } from '@/types';
import useBreadcrumbs from '@/store/useBreadcrumbs';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

useBreadcrumbs().set([
    {
        title: 'Profile settings',
        href: route('profile.edit'),
    },
]);

const page = usePage();
const user = page.props.auth.user as User;

const form = useForm({
    email: user.email,
    firstname: user.firstname,
    lastname: user.lastname ?? '', // TODO : won't this save empty string instead of null? double-check
});

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Profile settings" />

    <SettingsLayout>
        <div class="flex flex-col space-y-6">
            <HeadingSmall title="Profile information" description="Update your name and email address" />

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid gap-2">
                    <Label for="firstname">First Name</Label>
                    <Input id="firstname" class="mt-1 block w-full" v-model="form.firstname" required placeholder="First name" />
                    <InputError class="mt-2" :message="form.errors.firstname" />
                </div>
                <div class="grid gap-2">
                    <Label for="lastname">Last Name</Label>
                    <Input id="lastname" class="mt-1 block w-full" v-model="form.lastname" placeholder="Last name" />
                    <InputError class="mt-2" :message="form.errors.lastname" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        class="mt-1 block w-full"
                        v-model="form.email"
                        required
                        autocomplete="username"
                        placeholder="Email address"
                    />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div v-if="mustVerifyEmail && ! page.props.auth.verified">
                    <p class="-mt-4 text-sm text-muted-foreground">
                        Your email address is unverified.
                        <Link
                            :href="route('verification.send')"
                            method="post"
                            as="button"
                            class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                        >
                            Click here to resend the verification email.
                        </Link>
                    </p>

                    <div v-if="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-600">
                        A new verification link has been sent to your email address.
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <Button :disabled="form.processing">Save</Button>

                    <Transition
                        enter-active-class="transition ease-in-out"
                        enter-from-class="opacity-0"
                        leave-active-class="transition ease-in-out"
                        leave-to-class="opacity-0"
                    >
                        <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">Saved.</p>
                    </Transition>
                </div>
            </form>
        </div>
    </SettingsLayout>
</template>
