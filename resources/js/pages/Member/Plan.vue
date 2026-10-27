<script setup lang="ts">
import PlanView from '@/components/domain/PlanView.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { useProjectChanges } from '@/composables/useRealtime';
import { _ } from '@/composables/useTranslations';
import type { Plan } from '@/types/models';

const props = defineProps<{
  plan: Plan;
  family: { id: number; name: string; unit_type_id: number } | null;
}>();

const floors = computed(() =>
  [...new Set(props.plan.items.map((item) => item.floor))].sort((a, b) => a - b),
);
const floor = ref(floors.value[0] ?? 0);
const ownUnit = computed(
  () =>
    props.plan.items.find((item) => item.unit?.family?.id === props.family?.id)?.unit?.id ?? null,
);

useProjectChanges({ plan: ['plan'], units: ['plan'], lottery_runs: ['plan'] });
</script>

<template>
  <PageHeader
    :title="_('Plan')"
    :description="family ? _('Units of your family\'s type are highlighted.') : undefined"
  />

  <div v-if="floors.length > 1" class="mb-4 flex flex-wrap gap-2" role="tablist">
    <button
      v-for="f in floors"
      :key="f"
      type="button"
      role="tab"
      :aria-selected="floor === f"
      :class="[
        'min-h-11 rounded-lg border-2 px-4 font-medium',
        floor === f ? 'border-border-interactive bg-selection/40' : 'border-border',
      ]"
      @click="floor = f"
    >
      {{ f === 0 ? _('Ground floor') : _('Floor {n}', { n: f }) }}
    </button>
  </div>

  <PlanView :plan :floor :highlight-type="family?.unit_type_id" :own-unit="ownUnit" />
</template>
