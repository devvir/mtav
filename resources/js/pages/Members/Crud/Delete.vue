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
  member: Member;
}>();

const email = ref('');
const form = useForm({});

const destroy = () => {
  router.delete(route('members.destroy', props.member.id), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
    onFinish: () => form.reset(),
  });
};

const disabled = computed(() => email.value !== props.member.email || form.processing);
</script>

<template>
  <div class="space-y-3">
    <HeadingSmall
      title="Delete account"
      description="Delete this account and all of its resources"
      class="text-xs opacity-70"
    />

    <Dialog>
      <DialogTrigger as-child>
        <Button
          variant="ghost"
          size="sm"
          class="border border-red-900/40 text-xs text-red-400 hover:bg-red-950/30 hover:text-red-300"
        >
          {{ _('Delete account') }}
        </Button>
      </DialogTrigger>

      <DialogContent>
        <form class="space-y-6" @submit.prevent="destroy">
          <DialogHeader class="space-y-3">
            <DialogTitle>{{ _('Are you sure you want to delete this account?') }}</DialogTitle>
            <DialogDescription>
              {{
                _(
                  "Please enter the user's email to confirm that you would like to permanently delete their account.",
                )
              }}
            </DialogDescription>
          </DialogHeader>

          <div class="grid gap-2">
            <Label for="email" class="sr-only">{{ _('Email') }}</Label>
            <Input
              v-model="email"
              type="email"
              name="email"
              :placeholder="_('Account email')"
              autocomplete="off"
            />
          </div>

          <DialogFooter class="gap-2">
            <DialogClose as-child>
              <Button variant="secondary" @click="form.reset()">{{ _('Cancel') }}</Button>
            </DialogClose>

            <Button type="submit" variant="destructive" :disabled="disabled">{{
              _('Delete account')
            }}</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  </div>
</template>
