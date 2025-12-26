<script setup lang="ts">
import Polygon from './core/Polygon.vue';
import { useItem } from './composables/useItem';
import { useTextFitting } from './composables/useTextFitting';

interface ItemProps {
  item: PlanItem;
  highlighted?: boolean;
}

const emit = defineEmits<{
  click: [id: number];
  hover: [id: number, hovering: boolean];
}>();

const {
  item,
  highlighted = false,
} = defineProps<ItemProps>();

const label = computed(() => item.name || item.unit?.identifier || '');

const { style, centroid, textColor, isHovering } = useItem(item);
const { fontSize } = useTextFitting(item.polygon, label.value);

const handlePolygonClick = () => emit('click', item.id);
const handlePolygonHover = (hovering: boolean) => {
  isHovering.value = hovering;
  emit('hover', item.id, hovering);
};
</script>

<template>
  <g class="cursor-pointer">
    <!-- Polygon base with hover effect -->
    <Polygon
      :points="item.polygon"
      :fill="style.fill"
      :stroke="isHovering || highlighted ? '#fbbf24' : style.stroke"
      :stroke-width="highlighted ? style.strokeWidth + 2 : style.strokeWidth"
      :opacity="style.opacity"
      class="transition-all duration-150"
      @click="handlePolygonClick"
      @hover="handlePolygonHover"
    />

    <!-- Label text, centered on polygon -->
    <text
      v-if="label"
      :x="centroid[0]"
      :y="centroid[1]"
      :fill="textColor"
      :font-size="`${fontSize}px`"
      :title="label"
      text-anchor="middle"
      dominant-baseline="central"
      class="pointer-events-none select-none font-semibold line-clamp-2"
    >
      {{ label }}
    </text>
  </g>
</template>
