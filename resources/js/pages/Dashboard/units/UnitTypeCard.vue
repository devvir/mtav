<script setup lang="ts">
// Copilot - pending review
import { ModalLink } from '@inertiaui/modal-vue';
import UnitCard from './UnitCard.vue';
import { _ } from '@/composables/useTranslations';

defineProps<{
  unitType: {
    id: number;
    name: string;
    description?: string;
    units_count: number;
    units?: any[];
  };
}>();
</script>

<template>
  <div class="rounded-lg border border-sidebar-border/70 bg-card">
    <!-- Unit Type Header -->
    <ModalLink
      :href="`/unit-types/${unitType.id}`"
      class="block border-b border-sidebar-border/50 bg-muted/30 p-4 transition-colors hover:bg-muted/50"
    >
      <div class="flex items-center justify-between">
        <div>
          <div class="font-semibold">{{ unitType.name }}</div>
          <div v-if="unitType.description" class="mt-1 text-sm text-muted-foreground">
            {{ unitType.description }}
          </div>
        </div>
        <div class="text-sm text-muted-foreground">
          {{ unitType.units_count }} {{ unitType.units_count === 1 ? _('unit') : _('units') }}
        </div>
      </div>
    </ModalLink>

    <!-- Units Grid -->
    <div v-if="unitType.units && unitType.units.length > 0" class="grid gap-3 p-4 @md:grid-cols-2">
      <UnitCard v-for="unit in unitType.units" :key="unit.id" :unit="unit" />
    </div>

    <!-- Empty state -->
    <div v-else class="p-4 text-center text-sm text-muted-foreground">
      {{ _('No units of this type yet') }}
    </div>
  </div>
</template>
