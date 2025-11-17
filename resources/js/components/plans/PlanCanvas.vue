<script setup lang="ts">
import Plan from './Plan.vue';

interface Props {
  plan: Plan;
  highlightUnitId?: number;
}

interface Emits {
  unitClick: [unitId: number];
  unitHover: [unitId: number | null];
}

defineProps<Props>();
const emit = defineEmits<Emits>();

const handleUnitClick = (unitId: number) => {
  emit('unitClick', unitId);
};

const handleUnitHover = (unitId: number | null) => {
  emit('unitHover', unitId);
};
</script>

<template>
  <div class="w-full h-full relative">
    <Plan
      :plan="plan"
      :highlight-unit-id="highlightUnitId"
      @unit-click="handleUnitClick"
      @unit-hover="handleUnitHover"
    />
  </div>
</template>