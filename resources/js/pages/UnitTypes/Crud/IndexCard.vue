<script setup lang="ts">
import Card from '@/components/shared/Card.vue';
import { _ } from '@/composables/useTranslations';

defineProps<{
  unitType: ApiResource<UnitType>;
}>();
</script>

<template>
  <Card class="h-full">
    <template v-slot:header>
      <div class="flex items-center justify-between gap-3">
        <div class="flex-1 text-right">
          <div class="text-xs font-medium tracking-wide text-text-subtle uppercase">
            {{ _('Unit Type') }}
          </div>
          <div class="truncate text-xl font-semibold text-text">{{ unitType.name }}</div>
        </div>
      </div>
    </template>

    <div class="space-y-3">
      <div v-if="unitType.description" class="text-sm leading-relaxed text-text-muted">
        {{ unitType.description }}
      </div>

      <div class="flex gap-4 text-sm font-medium text-text-subtle">
        <div v-if="unitType.units_count !== undefined" class="flex items-center gap-1">
          <span class="font-semibold text-text">{{ unitType.units_count }}</span>
          <span>{{ _('Units') }}</span>
        </div>
        <div v-if="unitType.families_count !== undefined" class="flex items-center gap-1">
          <span class="font-semibold text-text">{{ unitType.families_count }}</span>
          <span>{{ _('Families') }}</span>
        </div>
      </div>
    </div>
  </Card>
</template>
