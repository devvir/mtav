<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

// Components
import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { User } from '@/types';

const props = defineProps<{
    user: User;
}>();

const email = ref('');
const form = useForm({});

const deleteUser = () => {
    router.delete(route('users.destroy', props.user.id), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onFinish: () => form.reset(),
    });
};

const disabled = computed(
    () => (email.value !== props.user.email) || form.processing
);
</script>

<template>
    <div class="flex-1 p-5 space-y-8 border rounded-md lg:min-w-[600px]">
        <HeadingSmall title="Delete account" description="Delete this account and all of its resources" />

        <div class="p-6 space-y-4 rounded-lg border border-red-100 bg-red-50 dark:border-red-200/10 dark:bg-red-700/10">
            <div class="relative space-y-1 text-red-600 dark:text-red-100">
                <p class="font-medium">Warning</p>
                <p class="text-xs">Please proceed with caution, this action cannot be undone.</p>
            </div>

            <Dialog>
                <DialogTrigger as-child class="mt-6">
                    <Button variant="destructive" class="hover:cursor-pointer">Delete account</Button>
                </DialogTrigger>

                <DialogContent>
                    <form class="space-y-8" @submit.prevent="deleteUser">
                        <DialogHeader class="space-y-3">
                            <DialogTitle>Are you sure you want to delete this account?</DialogTitle>
                            <DialogDescription>
                                Please enter the user's email to confirm that you would like to permanently delete their account.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="email" class="sr-only">Email</Label>
                            <Input v-model="email" type="email" name="email" placeholder="Account email" autocomplete="off" />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button variant="secondary" @click="form.reset()">Cancel</Button>
                            </DialogClose>

                            <Button type="submit" variant="destructive" :disabled="disabled">Delete account</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
