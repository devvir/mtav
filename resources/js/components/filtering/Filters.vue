<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';
import Sticky from '@/components/layout/header/Sticky.vue';
import { Search, Switch, Options, SEARCH, SWITCH, OPTIONS } from '.';
import type { FilterConfig, OptionValue } from '.';

const model = defineModel<Record<string, OptionValue>>({
  default: () => ({}),
});

const emit = defineEmits<{
  input: [data: Record<string, OptionValue>];
}>();

const props = defineProps<{
  config?: FilterConfig;
  filter?: (data: Record<string, OptionValue>) => void;
  autoFilter?: boolean;
  class?: HTMLAttributes['class'];
}>();

// Handle updates from child components
const handleUpdate = (key: string, value: OptionValue) => {
  const newData = { ...model.value, [key]: value };

  model.value = newData;

  emit('input', newData);

  // If filter function provided, call it (for navigation, etc.)
  const cancelAutofilter = props.filter?.(newData) === false;

  // Auto-filter: automatically reload with the selected data
  if (props.autoFilter && ! cancelAutofilter) {
    router.reload({ data: newData });
  }
};

// Generate components from config
const configEntries = computed(() => props.config ? Object.entries(props.config) : []);

// Propagate defaults to model (if they're passed via config instead of v-model)
watch(() => props.config, () => (model.value = Object.fromEntries(
  Object.entries(props.config ?? {}).map(
    ([k, v]: [string, any]) => [k, v.value ?? model.value[k]]
  )
)));
</script>

<template>
  <Sticky>
    <div :class="cn(
      'flex flex-col justify-between gap-wide lg:flex-row lg:items-center',
      'px-base py-wide backdrop-blur-sm',
      'border-b border-border/20 shadow-accent-foreground/25',
      $props.class
    )">
      <!-- Config-driven components -->
      <template v-if="config">
        <template v-for="[key, filterConfig] in configEntries" :key="key">
          <Search v-if="filterConfig.type === SEARCH" :model-value="model[key]"
            @input="handleUpdate(key, $event)" />
          <Switch v-else-if="filterConfig.type === SWITCH" :options="filterConfig.options || {}"
            :model-value="model[key]" @input="handleUpdate(key, $event)" />
          <Options v-else-if="filterConfig.type === OPTIONS" :options="filterConfig.options || {}"
            :model-value="model[key]" @input="handleUpdate(key, $event)" />
        </template>
      </template>

      <!-- Manual slot content -->
      <slot />
    </div>
  </Sticky>
</template>