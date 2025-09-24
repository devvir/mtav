<script setup lang="ts">
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
import { _ } from '@/composables/useTranslations';

const props = defineProps<{
  user: User;
}>();

const email = ref('');
const form = useForm({});

const destroy = () => {
  router.delete(route('users.destroy', props.user.id), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
    onFinish: () => form.reset(),
  });
};

const disabled = computed(() => email.value !== props.user.email || form.processing);
</script>

<template>
  <div class="flex-1 space-y-wide rounded-md border p-base">
    <HeadingSmall title="Delete account" description="Delete this account and all of its resources" />

    <div class="space-y-wide rounded-lg bg-destructive/4 p-6">
      <div class="text-destructive-foreground">
        <p class="text-xl font-bold tracking-wide">{{ _('Warning!') }}</p>
        <p class="text-xs leading-6">{{ _('Please proceed with caution, this action cannot be undone.') }}</p>
      </div>

      <Dialog>
        <DialogTrigger as-child>
          <Button variant="destructive" class="cursor-pointer !bg-destructive/60">
            {{ _('Delete account') }}
          </Button>
        </DialogTrigger>

        <DialogContent>
          <form class="space-y-8" @submit.prevent="destroy">
            <DialogHeader class="space-y-3">
              <DialogTitle>{{ _('Are you sure you want to delete this account?') }}</DialogTitle>
              <DialogDescription>
                {{
                  _("Please enter the user's email to confirm that you would like to permanently delete their account.")
                }}
              </DialogDescription>
            </DialogHeader>

            <div class="grid gap-2">
              <Label for="email" class="sr-only">{{ _('Email') }}</Label>
              <Input v-model="email" type="email" name="email" :placeholder="_('Account email')" autocomplete="off" />
            </div>

            <DialogFooter class="gap-2">
              <DialogClose as-child>
                <Button variant="secondary" @click="form.reset()">{{ _('Cancel') }}</Button>
              </DialogClose>

              <Button type="submit" variant="destructive" :disabled="disabled">{{ _('Delete account') }}</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  </div>
</template>
