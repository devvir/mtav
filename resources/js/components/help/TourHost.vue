<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { findTarget, useTour } from '@/composables/useTour';
import { _ } from '@/composables/useTranslations';
import { router } from '@inertiajs/vue3';
import { X } from 'lucide-vue-next';

/**
 * Plays the current tour: dims the page, lights up the step's element, and shows the
 * step beside it (at the bottom of the screen on phones). Keyboard: ← → Esc.
 */
const tour = useTour();
const step = computed(() => tour.current());

const rect = ref<DOMRect | null>(null);
const card = useTemplateRef<HTMLElement>('card');
const narrow = useMediaQuery('(max-width: 640px)');

const PAD = 8;

function measure() {
  const target = step.value && findTarget(step.value.target);
  rect.value = target?.getBoundingClientRect() ?? null;
}

watch(
  step,
  async (current) => {
    if (!current) return;
    findTarget(current.target)?.scrollIntoView({ block: 'center', behavior: 'smooth' });
    await new Promise((resolve) => setTimeout(resolve, 300));
    measure();
    await nextTick();
    card.value?.focus();
  },
  { immediate: true },
);

useEventListener(window, 'resize', measure);
useEventListener(window, 'scroll', measure, { passive: true, capture: true });
useEventListener(window, 'keydown', (event: KeyboardEvent) => {
  if (!step.value) return;
  if (event.key === 'Escape') tour.stop();
  if (event.key === 'ArrowRight') tour.next();
  if (event.key === 'ArrowLeft') tour.back();
});
router.on('navigate', () => tour.stop());

const spotlight = computed(() =>
  rect.value
    ? {
        top: `${rect.value.top - PAD}px`,
        left: `${rect.value.left - PAD}px`,
        width: `${rect.value.width + PAD * 2}px`,
        height: `${rect.value.height + PAD * 2}px`,
      }
    : null,
);

/** Below the element if it fits, else above it; pinned to the bottom on phones */
const position = computed(() => {
  if (narrow.value || !rect.value) return { left: '1rem', right: '1rem', bottom: '1rem' };

  const width = 352;
  const left = Math.min(Math.max(16, rect.value.left), window.innerWidth - width - 16);
  const below = rect.value.bottom + PAD + 12;

  return below + 220 < window.innerHeight
    ? { left: `${left}px`, top: `${below}px`, width: `${width}px` }
    : {
        left: `${left}px`,
        bottom: `${window.innerHeight - rect.value.top + PAD + 12}px`,
        width: `${width}px`,
      };
});
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200"
      enter-from-class="opacity-0"
      leave-active-class="transition duration-200"
      leave-to-class="opacity-0"
    >
      <div v-if="step" class="fixed inset-0 z-[70]">
        <!-- Click outside ends the tour -->
        <div class="absolute inset-0" @click="tour.stop()" />
        <div
          v-if="spotlight"
          class="pointer-events-none absolute rounded-xl ring-4 ring-interactive transition-all duration-300"
          :style="{ ...spotlight, boxShadow: '0 0 0 9999px rgb(0 0 0 / 0.55)' }"
        />
        <div v-else class="pointer-events-none absolute inset-0 bg-black/55" />

        <div
          ref="card"
          role="dialog"
          aria-modal="true"
          :aria-label="step.title"
          tabindex="-1"
          class="absolute rounded-2xl border-2 border-border bg-surface p-4 text-text shadow-2xl outline-none"
          :style="position"
        >
          <div class="flex items-start gap-3">
            <div class="min-w-0 flex-1" aria-live="polite">
              <p class="text-xs font-semibold text-text-muted">
                {{
                  _('Step {n} of {total}', {
                    n: tour.state.index + 1,
                    total: tour.state.steps.length,
                  })
                }}
              </p>
              <p class="mt-1 font-semibold">{{ step.title }}</p>
              <p class="mt-1 text-sm text-text-muted">{{ step.body }}</p>
            </div>
            <button
              type="button"
              class="-m-1 flex size-9 shrink-0 items-center justify-center rounded-full text-text-muted hover:bg-surface-interactive-hover"
              :aria-label="_('End the tour')"
              @click="tour.stop()"
            >
              <X class="size-5" />
            </button>
          </div>
          <div class="mt-4 flex justify-between gap-2">
            <Button
              variant="ghost"
              size="sm"
              :disabled="tour.state.index === 0"
              @click="tour.back()"
              >{{ _('Back') }}</Button
            >
            <Button size="sm" @click="tour.next()">
              {{ tour.state.index === tour.state.steps.length - 1 ? _('Done') : _('Next') }}
            </Button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
