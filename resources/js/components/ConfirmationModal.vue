<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { _ } from '@/composables/useTranslations';

defineEmits<{
  confirm: [];
  'update:open': [value: boolean];
}>();

const props = defineProps<{
  open?: boolean;
  title: string;
  description: string;
  expectedText: string;
  confirmButtonText: string;
  variant?: 'default' | 'destructive';
}>();

const confirmText = ref('');

const disabled = computed(() => new Intl.Collator('en', { sensitivity: 'base' }).compare(
  confirmText.value,
  props.expectedText,
));

// Clear input when modal closes
watch(() => props.open, (isOpen: boolean) => {
  if (! isOpen) confirmText.value = '';
});
</script>

<template>
  <Dialog :open @update:open="$emit('update:open', $event)">
    <DialogContent>
      <div class="space-y-6">
        <DialogHeader class="space-y-3">
          <DialogTitle>{{ title }}</DialogTitle>
          <DialogDescription>
            {{ description }}
          </DialogDescription>
        </DialogHeader>

        <div class="grid gap-2">
          <Label class="text-sm">
            {{ _('Please write the following to confirm this action') }}:
            <strong class="font-mono">{{ expectedText }}</strong>
          </Label>
          <Input
            v-model="confirmText"
            name="confirmation"
            autocomplete="off"
            :placeholder="expectedText"
            autofocus
            @keyup.enter="!disabled && $emit('confirm')"
          />
        </div>

        <DialogFooter class="gap-2">
          <DialogClose as-child>
            <Button variant="secondary">
              {{ _('Cancel') }}
            </Button>
          </DialogClose>

          <Button :variant="variant || 'default'" :disabled="disabled" @click="$emit('confirm')">
            {{ confirmButtonText }}
          </Button>
        </DialogFooter>
      </div>
    </DialogContent>
  </Dialog>
</template>
