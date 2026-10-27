<script setup lang="ts">
import { useToasts, type ToastTone } from '@/composables/useToasts';
import { _ } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';
import { Link, usePage } from '@inertiajs/vue3';
import { CircleAlert, CircleCheck, Info, TriangleAlert, X } from 'lucide-vue-next';

/**
 * Flash messages of each visit, and live notifications, as toasts.
 */
const page = usePage();
const { toasts, show, dismiss } = useToasts();

watch(
  () => page.props.flash,
  (flash) => {
    (['success', 'info', 'warning', 'error'] as ToastTone[]).forEach(
      (tone) => flash?.[tone] && show({ tone, message: flash[tone] as string }),
    );
  },
  { immediate: true, deep: true },
);

const icons = { success: CircleCheck, info: Info, warning: TriangleAlert, error: CircleAlert };
const tones = {
  success: 'border-success/40 [&_.icon]:text-success',
  info: 'border-info/40 [&_.icon]:text-info',
  warning: 'border-warning/50 [&_.icon]:text-warning',
  error: 'border-error/50 [&_.icon]:text-error',
};
</script>

<template>
  <div
    aria-live="polite"
    class="pointer-events-none fixed inset-x-0 bottom-20 z-[60] flex flex-col items-center gap-2 px-4 sm:inset-x-auto sm:right-4 sm:bottom-4 sm:items-end"
  >
    <TransitionGroup
      enter-active-class="transition duration-250"
      enter-from-class="translate-y-2 opacity-0"
      leave-active-class="transition duration-200"
      leave-to-class="opacity-0"
      move-class="transition duration-200"
    >
      <div
        v-for="toast in toasts"
        :key="toast.id"
        :role="toast.tone === 'error' ? 'alert' : 'status'"
        :class="
          cn(
            'pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl border-2 bg-surface p-3 shadow-lg',
            tones[toast.tone],
          )
        "
      >
        <component :is="icons[toast.tone]" class="icon mt-0.5 size-5 shrink-0" />
        <div class="min-w-0 flex-1 text-sm">
          <p v-if="toast.title" class="font-semibold">{{ toast.title }}</p>
          <p class="text-text">{{ toast.message }}</p>
          <Link
            v-if="toast.url"
            :href="toast.url"
            class="mt-1 inline-block font-medium text-text-link hover:underline"
            @click="dismiss(toast.id)"
          >
            {{ _('Open') }}
          </Link>
        </div>
        <button
          type="button"
          class="-m-1 flex size-8 shrink-0 items-center justify-center rounded-full text-text-muted hover:bg-surface-interactive-hover"
          :aria-label="_('Dismiss')"
          @click="dismiss(toast.id)"
        >
          <X class="size-4" />
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>
