<script setup lang="ts">
// Copilot - pending review
import { ModalLink } from '@inertiaui/modal-vue';
import { Building2 } from 'lucide-vue-next';
import { _ } from '@/composables/useTranslations';

defineProps<{
  unit: Unit & {
    number: string;
  };
}>();
</script>

<template>
  <ModalLink
    :href="`/units/${unit.id}`"
    class="flex items-center justify-between rounded-md border border-border bg-surface-elevated p-3 transition-all hover:border-border-interactive hover:shadow-md focus:outline-none focus:ring-2 focus:ring-focus-ring focus:ring-offset-2 focus:ring-offset-focus-ring-offset"
  >
    <div class="flex-1">
      <div class="text-sm font-medium text-text">{{ _('Unit') }} {{ unit.number }}</div>
      <div v-if="unit.type" class="text-xs text-text-muted">{{ unit.type.name }}</div>
      <div v-else-if="unit.family" class="text-xs text-text-muted">{{ unit.family.name }}</div>
    </div>
    <Building2 class="h-4 w-4 text-text-subtle" />
  </ModalLink>
</template>
