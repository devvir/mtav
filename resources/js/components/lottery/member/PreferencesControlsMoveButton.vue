<script setup lang="ts">
import { Button } from '@/components/ui/button';

defineProps<{
  disabled: boolean;
  label: string;
}>();

const emit = defineEmits<{
  click: [];
}>();
</script>

<template>
  <Button
    @click="emit('click')"
    :disabled="disabled"
    variant="ghost"
    size="sm"
    class="h-6 w-6 p-0"
    :aria-label="label"
  >
    <slot />
  </Button>
</template>
