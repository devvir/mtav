<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { TrophyIcon, SparklesIcon, UsersIcon, ChevronUpIcon, ChevronDownIcon } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

const isCollapsed = ref(false);

const toggleCollapse = () => {
  isCollapsed.value = !isCollapsed.value;
};
</script>

<template>
  <div
    class="relative overflow-hidden rounded-xl @sm:rounded-2xl bg-gradient-to-br from-primary/10 via-primary/5 to-transparent border border-primary/20 transition-all duration-300 ease-in-out"
  >
    <!-- Background decorative elements -->
    <div class="absolute inset-0">
      <div class="absolute top-2 right-4 @sm:top-4 @sm:right-8 h-16 w-16 @sm:h-32 @sm:w-32 rounded-full bg-primary/5 blur-xl @sm:blur-2xl"></div>
      <div class="absolute bottom-2 left-4 @sm:bottom-4 @sm:left-8 h-12 w-12 @sm:h-24 @sm:w-24 rounded-full bg-accent/10 blur-lg @sm:blur-xl"></div>
      <div class="absolute top-1/2 left-1/2 h-20 w-20 @sm:h-40 @sm:w-40 -translate-x-1/2 -translate-y-1/2 rounded-full bg-primary/3 blur-2xl @sm:blur-3xl"></div>
    </div>

    <!-- Content -->
    <div class="relative px-4 pb-4 pt-6 @sm:px-8 @sm:pt-12" :class="{'py-6!': isCollapsed }">
      <!-- Icon and main title section -->
      <div
        class="flex flex-col @sm:flex-row items-center justify-center mb-4"
        :class="isCollapsed ? '@sm:mb-0' : '@sm:mb-6'"
      >
        <div class="flex flex-col @lg:flex-row items-center gap-3 @lg:gap-wide">
          <div class="relative flex-shrink-0">
            <div class="absolute inset-0 rounded-full bg-primary/20 blur-md"></div>
            <div class="relative rounded-full bg-primary/10 p-3 @sm:p-4 backdrop-blur-sm border border-primary/20">
              <TrophyIcon class="h-6 w-6 @sm:h-8 @sm:w-8 text-primary" />
            </div>
          </div>

          <div class="text-center @lg:text-left">
            <h1 class="text-xl @sm:text-3xl @xl:text-4xl font-bold bg-gradient-to-r from-primary via-primary/80 to-primary/60 bg-clip-text text-transparent mb-1 @sm:mb-2 leading-tight px-2 @sm:px-0">
              {{ _('Fair distribution of Units among Families') }}
            </h1>
            <div class="flex items-center justify-center @lg:justify-start gap-2 text-primary/70">
              <SparklesIcon class="h-3 w-3 @sm:h-4 @sm:w-4 flex-shrink-0" />
              <span class="text-xs @sm:text-sm font-medium uppercase tracking-wide">{{ _('Units Lottery') }}</span>
              <SparklesIcon class="h-3 w-3 @sm:h-4 @sm:w-4 flex-shrink-0" />
            </div>
          </div>
        </div>
      </div>

      <!-- Description with enhanced styling -->
      <div :class="{'max-h-0': isCollapsed}" class="max-w-4xl mx-auto text-center transition-all overflow-hidden">
        <div class="bg-white/50 dark:bg-gray-900/30 backdrop-blur-sm rounded-lg @sm:rounded-xl p-3 @sm:p-6 border border-border/50 shadow-lg shadow-primary/5">
          <div class="flex items-center justify-center gap-2 mb-2 @sm:mb-3">
            <UsersIcon class="h-4 w-4 @sm:h-5 @sm:w-5 text-primary/70 flex-shrink-0" />
            <span class="text-xs @sm:text-sm font-semibold text-primary/80 uppercase tracking-wide">{{ _('How it works') }}</span>
          </div>
          <p class="text-sm @sm:text-base @lg:text-lg leading-relaxed text-text/80 px-1 @sm:px-0">
            {{ _('The lottery system ensures fair and transparent allocation of housing units. Each family submits their preferences, and our optimization algorithm finds the best possible assignment for everyone.') }}
          </p>

          <!-- Visual elements -->
          <div class="hidden @lg:flex flex-col @sm:flex-row items-center justify-center gap-3 @sm:gap-8 mt-3 @sm:mt-6 pt-3 @sm:pt-4 border-t border-border/30">
            <div class="flex items-center gap-3 @sm:gap-8 w-full @sm:w-auto">
              <div class="flex flex-col items-center gap-1 @sm:gap-2 flex-1 max-w-20 @sm:max-w-none">
                <div class="h-1.5 w-1.5 @sm:h-2 @sm:w-2 rounded-full bg-primary/60"></div>
                <span class="text-xs text-text/60 font-medium text-center @2xl:text-nowrap hyphens-auto">{{ _('Submit Preferences') }}</span>
              </div>
              <div class="h-px w-3 @sm:w-8 bg-gradient-to-r from-primary/20 via-primary/60 to-primary/20 flex-shrink-0"></div>
              <div class="flex flex-col items-center gap-1 @sm:gap-2 flex-1 max-w-20 @sm:max-w-none">
                <div class="h-1.5 w-1.5 @sm:h-2 @sm:w-2 rounded-full bg-primary/60"></div>
                <span class="text-xs text-text/60 font-medium text-center @2xl:text-nowrap hyphens-auto">{{ _('Algorithm Optimization') }}</span>
              </div>
              <div class="h-px w-3 @sm:w-8 bg-gradient-to-r from-primary/20 via-primary/60 to-primary/20 flex-shrink-0"></div>
              <div class="flex flex-col items-center gap-1 @sm:gap-2 flex-1 max-w-20 @sm:max-w-none">
                <div class="h-1.5 w-1.5 @sm:h-2 @sm:w-2 rounded-full bg-primary/60"></div>
                <span class="text-xs text-text/60 font-medium text-center @2xl:text-nowrap hyphens-auto">{{ _('Fair Assignment') }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Collapse/Expand Button -->
      <div class="flex justify-center mt-4">
        <Button
          @click="toggleCollapse"
          variant="ghost"
          size="sm"
          class="gap-2 text-primary/70 hover:text-primary hover:bg-primary/10 transition-colors"
        >
          <ChevronUpIcon v-if="!isCollapsed" class="size-4" />
          <ChevronDownIcon v-else class="size-4" />
          <span class="text-xs font-medium">{{ isCollapsed ? _('Show Details') : _('Hide Details') }}</span>
        </Button>
      </div>
    </div>
  </div>
</template>