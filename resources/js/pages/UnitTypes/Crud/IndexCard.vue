<script setup lang="ts">
// Copilot - pending review
import Card from '@/components/shared/Card.vue';
import { _ } from '@/composables/useTranslations';

defineProps<{
  unitType: UnitType;
}>();
</script>

<template>
  <Card class="h-full">
    <template v-slot:header>
      <div class="flex justify-between">
        <div class="truncate pl-base text-right">
          <div class="text-xs text-muted-foreground/50">{{ _('Unit Type') }}</div>
          <div class="truncate text-xl">{{ unitType.name }}</div>
        </div>
      </div>
    </template>

    <div class="my-base-y flex flex-col justify-between gap-1 lg:gap-1.5">
      <div v-if="unitType.description" class="text-sm text-muted-foreground">
        {{ unitType.description }}
      </div>

      <div class="mt-2 flex gap-4 text-sm text-muted-foreground">
        <div v-if="unitType.units_count !== undefined">{{ unitType.units_count }} {{ _('Units') }}</div>
        <div v-if="unitType.families_count !== undefined">{{ unitType.families_count }} {{ _('Families') }}</div>
      </div>
    </div>
  </Card>
</template>
