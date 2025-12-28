<script setup lang="ts">
import Item from './Item.vue';

interface UnitProps {
  unit: Unit & Pick<Unit, 'plan_item'>;
  highlighted?: boolean;
}

const emit = defineEmits<{
  click: [id: number];
  hover: [id: number, hovering: boolean];
}>();

const {
  unit,
  highlighted,
} = defineProps<UnitProps>();

const handleItemClick = (itemId: number) => emit('click', itemId);
const handleItemHover = (itemId: number, hovering: boolean) => emit('hover', itemId, hovering);
</script>

<template>
  <Item :item="unit" :highlighted
    @click="handleItemClick"
    @hover="handleItemHover"
  />
</template>
