<script setup lang="ts">
// Copilot - Pending review

import { Button } from '@/components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { ChevronDown } from 'lucide-vue-next';

defineEmits<{
  toggle: [];
}>();

const props = defineProps<{
  entityKey: string;
  isExpanded: boolean;
}>();

const entityName = computed(
  () => props.entityKey.charAt(0).toUpperCase() + props.entityKey.slice(1),
);
</script>

<template>
  <Collapsible
    :open="isExpanded"
    :id="entityKey"
    class="rounded-lg border border-border bg-surface"
  >
    <CollapsibleTrigger as-child>
      <Button
        variant="ghost"
        class="h-auto w-full cursor-pointer justify-between bg-muted/50 py-10 text-left"
        @click="$emit('toggle')"
      >
        <div class="flex items-center gap-4 px-4">
          <div class="flex flex-col items-start">
            <h2 class="text-xl font-semibold text-foreground">{{ entityName }}</h2>
            <p class="text-sm text-muted-foreground">{{ entityName }} management forms</p>
          </div>
        </div>
        <ChevronDown
          :class="[
            'h-6 w-6 shrink-0 transition-transform duration-200',
            isExpanded ? 'rotate-180 transform' : '',
          ]"
        />
      </Button>
    </CollapsibleTrigger>

    <CollapsibleContent class="mt-4 px-10 pb-8">
      <slot />
    </CollapsibleContent>
  </Collapsible>
</template>
