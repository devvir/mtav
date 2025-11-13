<script setup lang="ts">
import { Badge, type BadgeVariant, type BadgeSize } from '@/components/badge';

/**
 * Binary Badge
 *
 * - <BinaryBadge when="condition" then="yes" else="no" variant="info" />
 *   Show badge with text 'yes' or 'no' according to condition, and both show as 'info'
 *
 * - <BinaryBadge when="condition" then="yes" else="no" :variants="['info', 'warning']" />
 *   Show badge with text 'yes' as 'info', or 'no' as 'warning', according to condition
 *
 * - <BinaryBadge when="condition" then="yes" variant='success' />
 *   Show badge with text 'yes', as 'success', only if condition (otherwise do not show)
 *
 * Note: the variant(s) default to 'default' if condition, 'secondary' otherwise
 */

const props = defineProps<{
  when: boolean | unknown; // Anything truthy/falsy should work
  then: string;
  else?: string;
  variant?: BadgeVariant;
  variants?: [BadgeVariant, BadgeVariant];
  size?: BadgeSize;
}>();

const useVariant = computed(() => props.variant
  ? [props.variant, props.variant]
  : [props.variants?.[0] ?? 'info', props.variants?.[1] ?? 'warning']);
</script>

<template>
  <Badge
    v-if="when || props.else"
    :size
    :variant="when ? useVariant[0] : useVariant[1]"
  >
    {{ when ? then : props.else }}
  </Badge>
</template>