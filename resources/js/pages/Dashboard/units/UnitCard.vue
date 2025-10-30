<script setup lang="ts">
// Copilot - pending review
import { ModalLink } from '@inertiaui/modal-vue';
import { Building2 } from 'lucide-vue-next';
import { _ } from '@/composables/useTranslations';

defineProps<{
  unit: {
    id: number;
    number: string;
    type?: {
      name: string;
    };
    family?: {
      name: string;
    };
  };
}>();
</script>

<template>
  <ModalLink
    :href="`/units/${unit.id}`"
    class="flex items-center justify-between rounded-md border border-sidebar-border/50 bg-card p-3 transition-all hover:border-primary/50 hover:shadow-sm"
  >
    <div class="flex-1">
      <div class="text-sm font-medium">{{ _('Unit') }} {{ unit.number }}</div>
      <div v-if="unit.type" class="text-xs text-muted-foreground">{{ unit.type.name }}</div>
      <div v-else-if="unit.family" class="text-xs text-muted-foreground">{{ unit.family.name }}</div>
    </div>
    <Building2 class="h-4 w-4 text-muted-foreground/50" />
  </ModalLink>
</template>
