<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { ChevronDown } from 'lucide-vue-next';

defineEmits<{
  toggle: [];
}>();

defineProps<{
  entity: {
    key: string;
    name: string;
    description: string;
    icon: any;
    color: string;
    component: any;
  };
  entityData: ApiResource[];
  isExpanded: boolean;
}>();
</script>

<template>
  <Collapsible :open="isExpanded" class="rounded-lg border border-border bg-surface">
    <CollapsibleTrigger as-child>
      <Button
        variant="ghost"
        class="h-auto w-full cursor-pointer justify-between bg-muted/50 py-10 text-left"
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
            'h-6 w-6 shrink-0 transition-transform duration-200',
            isExpanded ? 'rotate-180 transform' : '',
          ]"
        />
      </Button>
    </CollapsibleTrigger>

    <CollapsibleContent class="mt-4 px-10 pb-8">
      <component :is="entity.component" :entity="entity" :entity-data="entityData" />
    </CollapsibleContent>
  </Collapsible>
</template>
