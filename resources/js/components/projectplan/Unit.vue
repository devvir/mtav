<script setup lang="ts">
import type { PolygonConfig } from './types';
import Item from './Item.vue';

interface UnitProps {
  unit: Unit & Pick<Unit, 'plan_item'>;
  config: PolygonConfig;
  label?: string;
  highlighted?: boolean;
}

const emit = defineEmits<{
  click: [id: number];
  hover: [id: number, hovering: boolean];
}>();

const {
  unit,
  config,
  label,
  highlighted,
} = defineProps<UnitProps>();

const handleItemClick = (itemId: number) => emit('click', itemId);
const handleItemHover = (itemId: number, hovering: boolean) => emit('hover', itemId, hovering);
</script>

<template>
  <Item :config :label :highlighted
    :item="unit"
    @click="handleItemClick"
    @hover="handleItemHover"
  />
</template>
