<script setup lang="ts">
import { Alert, AlertDescription } from '@/components/alert';
import { Button } from '@/components/ui/button';
import { AlertCircle, AlertTriangle, CheckCircle2, Info, X } from 'lucide-vue-next';
import type { MessageType } from './useFlashMessages';

defineProps<{
  type: MessageType;
  message: string;
  multiline?: boolean;
}>();

const emit = defineEmits<{
  dismiss: [];
}>();

const icons: Record<MessageType, any> = {
  success: CheckCircle2,
  info: Info,
  warning: AlertTriangle,
  error: AlertCircle,
};
</script>

<template>
  <Alert :variant="type" class="flex items-center gap-3 transition-all duration-300">
    <div>
      <component :is="icons[type]" class="size-6" />
    </div>

    <AlertDescription :title="message" :class="multiline ? 'flex-1' : 'flex-1 truncate'">
      {{ message }}
    </AlertDescription>

    <Button variant="ghost" size="icon" class="h-6 w-6 shrink-0" @click="emit('dismiss')">
      <X class="h-4 w-4" />
    </Button>
  </Alert>
</template>
