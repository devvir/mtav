<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { useLocalState } from '@/composables/useLocalState';
import { _ } from '@/composables/useTranslations';
import {
  ChevronDownIcon,
  ChevronUpIcon,
  SparklesIcon,
  TrophyIcon,
  UsersIcon,
} from 'lucide-vue-next';

const { get, set } = useLocalState();
const isCollapsed = ref(get('lottery-hero-collapsed', false));

const toggleCollapse = () => {
  isCollapsed.value = !isCollapsed.value;
  set('lottery-hero-collapsed', isCollapsed.value);
};
</script>

<template>
  <div
    class="relative overflow-hidden rounded-xl border border-primary/20 bg-linear-to-br from-primary/10 via-primary/5 to-transparent transition-all duration-300 ease-in-out @sm:rounded-2xl"
  >
    <!-- Background decorative elements -->
    <div class="absolute inset-0">
      <div
        class="absolute top-2 right-4 h-16 w-16 rounded-full bg-primary/5 blur-xl @sm:top-4 @sm:right-8 @sm:h-32 @sm:w-32 @sm:blur-2xl"
      ></div>
      <div
        class="absolute bottom-2 left-4 h-12 w-12 rounded-full bg-accent/10 blur-lg @sm:bottom-4 @sm:left-8 @sm:h-24 @sm:w-24 @sm:blur-xl"
      ></div>
      <div
        class="absolute top-1/2 left-1/2 h-20 w-20 -translate-x-1/2 -translate-y-1/2 rounded-full bg-primary/3 blur-2xl @sm:h-40 @sm:w-40 @sm:blur-3xl"
      ></div>
    </div>

    <!-- Content -->
    <div class="relative px-4 pt-6 pb-4 @sm:px-8 @sm:pt-12" :class="{ 'py-6!': isCollapsed }">
      <!-- Icon and main title section -->
      <div
        class="mb-4 flex flex-col items-center justify-center @sm:flex-row"
        :class="isCollapsed ? '@sm:mb-0' : '@sm:mb-6'"
      >
        <div class="flex flex-col items-center gap-3 @lg:flex-row @lg:gap-wide">
          <div class="relative flex-shrink-0">
            <div class="absolute inset-0 rounded-full bg-primary/20 blur-md"></div>
            <div
              class="relative rounded-full border border-primary/20 bg-primary/10 p-3 backdrop-blur-sm @sm:p-4"
            >
              <TrophyIcon class="h-6 w-6 text-primary @sm:h-8 @sm:w-8" />
            </div>
          </div>

          <div class="text-center @lg:text-left">
            <h1
              class="mb-1 bg-gradient-to-r from-primary via-primary/80 to-primary/60 bg-clip-text px-2 text-xl leading-tight font-bold text-transparent @sm:mb-2 @sm:px-0 @sm:text-3xl @xl:text-4xl"
            >
              {{ _('Fair distribution of Units among Families') }}
            </h1>
            <div class="flex items-center justify-center gap-2 text-primary/70 @lg:justify-start">
              <SparklesIcon class="h-3 w-3 flex-shrink-0 @sm:h-4 @sm:w-4" />
              <span class="text-xs font-medium tracking-wide uppercase @sm:text-sm">{{
                _('Units Lottery')
              }}</span>
              <SparklesIcon class="h-3 w-3 flex-shrink-0 @sm:h-4 @sm:w-4" />
            </div>
          </div>
        </div>
      </div>

      <!-- Description with enhanced styling -->
      <div
        :class="{ 'max-h-0': isCollapsed }"
        class="mx-auto max-w-4xl overflow-hidden text-center transition-all"
      >
        <div
          class="rounded-lg border border-border/50 bg-white/50 p-3 shadow-lg shadow-primary/5 backdrop-blur-sm @sm:rounded-xl @sm:p-6 dark:bg-gray-900/30"
        >
          <div class="mb-2 flex items-center justify-center gap-2 @sm:mb-3">
            <UsersIcon class="h-4 w-4 flex-shrink-0 text-primary/70 @sm:h-5 @sm:w-5" />
            <span
              class="text-xs font-semibold tracking-wide text-primary/80 uppercase @sm:text-sm"
              >{{ _('How it works') }}</span
            >
          </div>
          <p class="px-1 text-sm leading-relaxed text-text/80 @sm:px-0 @sm:text-base @lg:text-lg">
            {{
              _(
                'The lottery system ensures fair and transparent allocation of housing units. Each family submits their preferences, and our optimization algorithm finds the best possible assignment for everyone.',
              )
            }}
          </p>

          <!-- Visual elements -->
          <div
            class="mt-3 hidden flex-col items-center justify-center gap-3 border-t border-border/30 pt-3 @sm:mt-6 @sm:flex-row @sm:gap-8 @sm:pt-4 @lg:flex"
          >
            <div class="flex w-full items-center gap-3 @sm:w-auto @sm:gap-8">
              <div
                class="flex max-w-20 flex-1 flex-col items-center gap-1 @sm:max-w-none @sm:gap-2"
              >
                <div class="h-1.5 w-1.5 rounded-full bg-primary/60 @sm:h-2 @sm:w-2"></div>
                <span
                  class="text-center text-xs font-medium hyphens-auto text-text/60 @2xl:text-nowrap"
                  >{{ _('Submit Preferences') }}</span
                >
              </div>
              <div
                class="h-px w-3 flex-shrink-0 bg-gradient-to-r from-primary/20 via-primary/60 to-primary/20 @sm:w-8"
              ></div>
              <div
                class="flex max-w-20 flex-1 flex-col items-center gap-1 @sm:max-w-none @sm:gap-2"
              >
                <div class="h-1.5 w-1.5 rounded-full bg-primary/60 @sm:h-2 @sm:w-2"></div>
                <span
                  class="text-center text-xs font-medium hyphens-auto text-text/60 @2xl:text-nowrap"
                  >{{ _('Algorithm Optimization') }}</span
                >
              </div>
              <div
                class="h-px w-3 flex-shrink-0 bg-gradient-to-r from-primary/20 via-primary/60 to-primary/20 @sm:w-8"
              ></div>
              <div
                class="flex max-w-20 flex-1 flex-col items-center gap-1 @sm:max-w-none @sm:gap-2"
              >
                <div class="h-1.5 w-1.5 rounded-full bg-primary/60 @sm:h-2 @sm:w-2"></div>
                <span
                  class="text-center text-xs font-medium hyphens-auto text-text/60 @2xl:text-nowrap"
                  >{{ _('Fair Assignment') }}</span
                >
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Collapse/Expand Button -->
      <div class="mt-4 flex justify-center">
        <Button
          @click="toggleCollapse"
          variant="ghost"
          size="sm"
          class="gap-2 text-primary/70 transition-colors hover:bg-primary/10 hover:text-primary"
        >
          <ChevronUpIcon v-if="!isCollapsed" class="size-4" />
          <ChevronDownIcon v-else class="size-4" />
          <span class="text-xs font-medium">{{
            isCollapsed ? _('Show Details') : _('Hide Details')
          }}</span>
        </Button>
      </div>
    </div>
  </div>
</template>
