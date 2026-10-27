<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { useDialogQueue } from '@/composables/useDialogs';
import { _ } from '@/composables/useTranslations';
import Field from '../form/Field.vue';
import TextArea from '../form/TextArea.vue';
import TextInput from '../form/TextInput.vue';
import Modal from './Modal.vue';

/**
 * Renders the confirm() and prompt() dialogs asked for from code (useDialogs.ts).
 */
const queue = useDialogQueue();
const request = computed(() => queue.current());

const open = ref(false);
const answer = ref('');

watch(request, (current) => {
  answer.value = '';
  open.value = Boolean(current);
});

const canConfirm = computed(() => {
  const current = request.value;
  if (!current) return false;
  if (current.kind === 'confirm') {
    return !current.options.typeToConfirm || answer.value.trim() === current.options.typeToConfirm;
  }
  return answer.value.trim().length >= (current.options.minLength ?? 1);
});

function settle(confirmed: boolean) {
  const current = request.value;
  if (!current) return;

  open.value = false;
  queue.settle(current.kind === 'confirm' ? confirmed : confirmed ? answer.value.trim() : null);
}

/** Closing the modal (Escape, backdrop, ×) cancels */
watch(open, (isOpen) => !isOpen && request.value && settle(false));
</script>

<template>
  <Modal
    v-if="request"
    v-model:open="open"
    :title="request.options.title"
    :description="request.options.message"
    size="sm"
  >
    <form id="dialog-host-form" class="space-y-4" @submit.prevent="canConfirm && settle(true)">
      <Field
        v-if="request.kind === 'confirm' && request.options.typeToConfirm"
        :label="_('Type {text} to confirm', { text: request.options.typeToConfirm })"
        v-slot="{ id }"
      >
        <TextInput :id v-model="answer" autocomplete="off" autofocus />
      </Field>

      <Field v-if="request.kind === 'prompt'" :label="request.options.label" v-slot="{ id }">
        <TextArea
          v-if="request.options.multiline"
          :id
          v-model="answer"
          :placeholder="request.options.placeholder"
          autofocus
        />
        <TextInput
          v-else
          :id
          v-model="answer"
          :placeholder="request.options.placeholder"
          autofocus
        />
      </Field>
    </form>

    <template #footer>
      <Button variant="outline" @click="settle(false)">{{
        request.options.cancelLabel ?? _('Cancel')
      }}</Button>
      <Button
        type="submit"
        form="dialog-host-form"
        :variant="request.options.danger ? 'destructive' : 'default'"
        :disabled="!canConfirm"
      >
        {{ request.options.confirmLabel ?? _('Confirm') }}
      </Button>
    </template>
  </Modal>
</template>
