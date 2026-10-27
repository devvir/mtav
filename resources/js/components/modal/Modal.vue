<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';
import { X } from 'lucide-vue-next';
import {
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogOverlay,
  DialogPortal,
  DialogRoot,
  DialogTitle,
} from 'reka-ui';

/**
 * Our modal: a sheet rising from the bottom on phones, a centred panel on larger
 * screens. Focus is trapped and restored, Escape and the backdrop close it — unless
 * `persistent`, for forms that would lose their input.
 */
const props = withDefaults(
  defineProps<{
    title: string;
    description?: string;
    size?: 'sm' | 'md' | 'lg';
    persistent?: boolean;
  }>(),
  { size: 'md', persistent: false },
);

const open = defineModel<boolean>('open', { default: false });

const width = { sm: 'sm:max-w-md', md: 'sm:max-w-lg', lg: 'sm:max-w-2xl' };

const guard = (event: Event) => props.persistent && event.preventDefault();
</script>

<template>
  <DialogRoot v-model:open="open">
    <slot name="trigger" />

    <DialogPortal>
      <DialogOverlay
        class="fixed inset-0 z-40 bg-black/40 backdrop-blur-[2px] data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:animate-in data-[state=open]:fade-in-0"
      />

      <DialogContent
        :class="
          cn(
            'fixed inset-x-0 bottom-0 z-50 flex max-h-[92dvh] flex-col rounded-t-2xl border-2 border-border bg-surface text-text shadow-2xl',
            'data-[state=closed]:animate-out data-[state=closed]:slide-out-to-bottom data-[state=open]:animate-in data-[state=open]:slide-in-from-bottom',
            'sm:inset-x-auto sm:top-1/2 sm:bottom-auto sm:left-1/2 sm:w-full sm:-translate-x-1/2 sm:-translate-y-1/2 sm:rounded-2xl',
            'sm:data-[state=closed]:slide-out-to-bottom-0 sm:data-[state=closed]:zoom-out-95 sm:data-[state=open]:slide-in-from-bottom-0 sm:data-[state=open]:zoom-in-95',
            'duration-200',
            width[size],
          )
        "
        @escape-key-down="guard"
        @pointer-down-outside="guard"
        @interact-outside="guard"
      >
        <header class="flex items-start gap-4 border-b border-border-subtle px-5 py-4">
          <div class="min-w-0 flex-1">
            <DialogTitle class="text-lg font-semibold">{{ title }}</DialogTitle>
            <DialogDescription v-if="description" class="mt-1 text-sm text-text-muted">
              {{ description }}
            </DialogDescription>
          </div>

          <DialogClose
            class="-m-2 flex size-11 shrink-0 items-center justify-center rounded-full text-text-muted hover:bg-surface-interactive-hover hover:text-text focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none"
          >
            <X class="size-5" />
            <span class="sr-only">{{ _('Close') }}</span>
          </DialogClose>
        </header>

        <div class="flex-1 overflow-y-auto px-5 py-4">
          <slot :close="() => (open = false)" />
        </div>

        <footer
          v-if="$slots.footer"
          class="flex flex-col-reverse gap-2 border-t border-border-subtle px-5 py-4 sm:flex-row sm:justify-end"
        >
          <slot name="footer" :close="() => (open = false)" />
        </footer>
      </DialogContent>
    </DialogPortal>
  </DialogRoot>
</template>
