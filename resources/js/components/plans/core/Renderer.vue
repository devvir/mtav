<script setup lang="ts">
import KonvaShape from './KonvaShape.vue';
import type { ShapeConfig, BoundaryConfig, CanvasConfig } from '../types';

const emit = defineEmits<{
  shapeClick: [id?: string];
  shapeMouseEnter: [id?: string];
  shapeMouseLeave: [id?: string];
}>();

defineProps<{
  shapes: ShapeConfig[];
  config: CanvasConfig;
  boundary: BoundaryConfig;
}>();
</script>

<template>
  <div
    class="inline-block relative rounded-lg border-2 border-dashed border-border bg-background"
    :style="{ width: `${config.width}px`, height: `${config.height}px` }"
  >
    <v-stage :config>
      <v-layer>
        <v-line v-if="boundary.points.length > 0" :config="{...boundary, closed: true}" />

        <KonvaShape
          v-for="(shape, index) in shapes"
          :key="`${shape.id || index}`"
          :config="shape"
          @click="(id?: string) => emit('shapeClick', id)"
          @mouseenter="(id?: string) => emit('shapeMouseEnter', id)"
          @mouseleave="(id?: string) => emit('shapeMouseLeave', id)" />
      </v-layer>
    </v-stage>
  </div>
</template>
