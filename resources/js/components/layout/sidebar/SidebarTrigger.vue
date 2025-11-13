<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { _ } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';
import { PanelLeftClose } from 'lucide-vue-next';
import type { HTMLAttributes } from 'vue';
import { useSidebar } from './utils';

defineProps<{
  class?: HTMLAttributes['class'];
}>();

const { toggleSidebar, open } = useSidebar();
</script>

<template>
  <Button
    data-sidebar="trigger"
    data-slot="sidebar-trigger"
    variant="ghost"
    size="icon"
    class="cursor-pointer"
    :class="cn('focus-visible-within:text-accent-foreground size-6 cursor-pointer', $props.class)"
    @click="toggleSidebar"
  >
    <div class="text-foreground opacity-65 hocus:opacity-100">
      <PanelLeftClose
        class="size-6 stroke-current duration-800 ease-out"
        :class="{ 'rotate-180': !open }"
      />
      <span class="sr-only">{{ _('Toggle Sidebar') }}</span>
    </div>
  </Button>
</template>
