<script setup lang="ts">
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Button } from '@/components/ui/button';
import { ChevronDown } from 'lucide-vue-next';

defineProps<{
  entity: {
    key: string;
    name: string;
    description: string;
    icon: any;
    color: string;
    component: any;
  };
  isExpanded: boolean;
}>();

defineEmits<{
  toggle: [];
}>();
</script>

<template>
  <Collapsible
    :open="isExpanded"
    class="border border-border rounded-lg bg-surface"
  >
    <CollapsibleTrigger as-child>
      <Button
        variant="ghost"
        class="w-full justify-between py-10 h-auto text-left bg-muted/50 cursor-pointer"
        @click="$emit('toggle')"
      >
        <div class="flex items-center gap-4 px-4">
          <component :is="entity.icon" :class="['size-7', entity.color]" />
          <div class="flex flex-col items-start">
            <h2 class="text-xl font-semibold">{{ entity.name }}</h2>
            <p class="text-sm text-text-muted">{{ entity.description }}</p>
          </div>
        </div>
        <ChevronDown
          :class="[
            'h-6 w-6 transition-transform duration-200 shrink-0',
            isExpanded ? 'transform rotate-180' : ''
          ]"
        />
      </Button>
    </CollapsibleTrigger>

    <CollapsibleContent class="mt-4 px-10 pb-8">
      <component :is="entity.component" :entity="entity" />
    </CollapsibleContent>
  </Collapsible>
</template>