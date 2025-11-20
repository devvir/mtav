<script setup lang="ts">
import { HeartIcon } from 'lucide-vue-next';
import { _ } from '@/composables/useTranslations';

defineProps<{
  unit: Unit;
  index: number;
}>();
</script>

<template>
  <div class="flex items-center gap-2">
    <h3 class="font-medium text-text truncate">{{ unit.identifier }}</h3>
    <span v-if="index < 3" class="ml-auto flex-shrink-0 inline-flex items-center gap-1 rounded-full bg-primary px-2 py-1 text-xs font-medium text-primary-foreground">
      <HeartIcon class="size-3" /> {{ _(`Choice #{numeral}`, { numeral: '' + (index + 1) }) }}
    </span>
  </div>
</template>
