<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { MapPinIcon } from 'lucide-vue-next';

defineProps<{
  message?: string;
}>();
</script>

<template>
  <div class="py-12 text-center">
    <slot name="icon">
      <MapPinIcon class="mx-auto h-12 w-12 text-text-subtle" />
    </slot>
    <p class="mt-2 text-sm text-text-muted">
      <slot>
        {{ message || _('There are no units in your assigned unit type to set preferences for.') }}
      </slot>
    </p>
  </div>
</template>
