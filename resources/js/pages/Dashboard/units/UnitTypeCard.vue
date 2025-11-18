<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { ModalLink } from '@inertiaui/modal-vue';
import UnitCard from './UnitCard.vue';

defineProps<{
  unitType: UnitType;
}>();
</script>

<template>
  <div class="overflow-hidden rounded-lg border border-border bg-surface-elevated shadow-sm">
    <!-- Unit Type Header -->
    <ModalLink
      :href="route('unit_types.show', unitType.id)"
      class="block border-b border-border-subtle bg-surface-sunken p-4 transition-all hover:bg-surface-interactive-hover focus:ring-2 focus:ring-focus-ring focus:ring-offset-2 focus:ring-offset-focus-ring-offset focus:outline-none"
    >
      <div class="flex items-center justify-between">
        <div>
          <div class="font-semibold text-text">{{ unitType.name }}</div>
          <div v-if="unitType.description" class="mt-1 text-sm text-text-muted">
            {{ unitType.description }}
          </div>
        </div>
        <div class="text-sm text-text-subtle">
          {{ unitType.units_count }} {{ unitType.units_count === 1 ? _('Unit') : _('Units') }}
        </div>
      </div>
    </ModalLink>

    <!-- Units Grid -->
    <div v-if="unitType.units && unitType.units.length > 0" class="grid gap-3 p-4 @md:grid-cols-2">
      <UnitCard v-for="unit in unitType.units" :key="unit.id" :unit="unit" />
    </div>

    <div v-else class="p-4 text-center text-sm text-text-muted">
      {{ _('No Units of this type yet') }}
    </div>
  </div>
</template>
