<script setup lang="ts">
import { startTour } from '@/composables/useTour';
import { _ } from '@/composables/useTranslations';
import { CircleHelp, Play } from 'lucide-vue-next';
import {
  PopoverArrow,
  PopoverClose,
  PopoverContent,
  PopoverPortal,
  PopoverRoot,
  PopoverTrigger,
} from 'reka-ui';

/**
 * A (?) next to something that needs explaining: a short text, and — where a tour
 * exists for it — "Show me how", which walks through it on the page.
 */
const props = defineProps<{ title: string; tour?: string }>();

const open = ref(false);

function showMe() {
  open.value = false;
  if (props.tour) nextTick(() => startTour(props.tour!));
}
</script>

<template>
  <PopoverRoot v-model:open="open">
    <PopoverTrigger
      class="inline-flex size-9 shrink-0 items-center justify-center rounded-full align-middle text-text-muted transition hover:bg-surface-interactive-hover hover:text-text focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none"
      :aria-label="_('About: {topic}', { topic: title })"
    >
      <CircleHelp class="size-5" />
    </PopoverTrigger>
    <PopoverPortal>
      <PopoverContent
        side="bottom"
        align="start"
        :side-offset="6"
        :collision-padding="12"
        class="z-50 w-[min(22rem,calc(100vw-2rem))] rounded-xl border-2 border-border bg-surface p-4 text-sm text-text shadow-xl data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:animate-in data-[state=open]:fade-in-0 data-[state=open]:zoom-in-95"
      >
        <p class="mb-1 font-semibold">{{ title }}</p>
        <div class="space-y-2 text-text-muted"><slot /></div>
        <div class="mt-3 flex items-center justify-between gap-2">
          <PopoverClose
            class="min-h-9 rounded-lg px-2 font-medium text-text-muted hover:text-text"
            >{{ _('Got it') }}</PopoverClose
          >
          <button
            v-if="tour"
            type="button"
            class="inline-flex min-h-9 items-center gap-1.5 rounded-lg bg-interactive px-3 font-medium text-interactive-foreground hover:bg-interactive-hover"
            @click="showMe"
          >
            <Play class="size-4" /> {{ _('Show me how') }}
          </button>
        </div>
        <PopoverArrow class="fill-border" />
      </PopoverContent>
    </PopoverPortal>
  </PopoverRoot>
</template>
