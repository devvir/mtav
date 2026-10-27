<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import type { Plan, PlanItem } from '@/types/models';

/**
 * The project plan, read-only: the boundary and every shape, units labelled.
 * Units of the viewer's type stand out; the viewer's own unit most.
 */
const props = defineProps<{
  plan: Plan;
  floor?: number;
  highlightType?: number | null;
  ownUnit?: number | null;
}>();

const items = computed(() => props.plan.items.filter((item) => item.floor === (props.floor ?? 0)));

const points = (polygon: [number, number][]) => polygon.map(([x, y]) => `${x},${y}`).join(' ');

const centre = (item: PlanItem) => {
  const xs = item.polygon.map(([x]) => x);
  const ys = item.polygon.map(([, y]) => y);
  return [(Math.min(...xs) + Math.max(...xs)) / 2, (Math.min(...ys) + Math.max(...ys)) / 2];
};

const fill = (item: PlanItem) => {
  if (item.unit && item.unit.id === props.ownUnit) return 'var(--success)';
  if (item.unit && props.highlightType && item.unit.unit_type_id === props.highlightType)
    return 'var(--interactive)';
  if (item.unit) return 'var(--border-strong)';
  return (item.metadata?.color as string) ?? 'var(--surface-sunken)';
};
</script>

<template>
  <svg
    :viewBox="`0 0 ${plan.width} ${plan.height}`"
    class="h-auto w-full rounded-2xl border-2 border-border bg-surface"
    role="img"
    :aria-label="_('Project plan')"
  >
    <polygon
      :points="points(plan.polygon)"
      fill="var(--surface-sunken)"
      stroke="var(--border-strong)"
      stroke-width="2"
    />
    <g v-for="item in items" :key="item.id">
      <polygon
        :points="points(item.polygon)"
        :fill="fill(item)"
        fill-opacity="0.85"
        stroke="var(--text)"
        stroke-opacity="0.35"
        stroke-width="1"
      />
      <text
        v-if="item.unit || item.name"
        :x="centre(item)[0]"
        :y="centre(item)[1]"
        text-anchor="middle"
        dominant-baseline="middle"
        font-size="12"
        font-weight="600"
        fill="var(--text)"
      >
        {{ item.unit?.identifier ?? item.name }}
      </text>
      <title>
        {{
          item.unit
            ? `${item.unit.identifier}${item.unit.family ? ` · ${item.unit.family.name}` : ''}`
            : item.name
        }}
      </title>
    </g>
  </svg>
</template>
