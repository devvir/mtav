<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance';
import { _ } from '@/composables/useTranslations';
import {
  Circle,
  Contrast,
  Droplet,
  Monitor,
  Moon,
  Sparkles,
  Sun,
  Sunset as SunsetIcon,
  Trees,
} from 'lucide-vue-next';

const { mode, colorTheme, updateMode, updateColorTheme } = useAppearance();

// Debug: Show current HTML classes
const htmlClasses = ref('');
onMounted(() => {
  const updateHtmlClasses = () => {
    htmlClasses.value = document.documentElement.className;
  };
  updateHtmlClasses();

  // Watch for class changes
  const observer = new MutationObserver(updateHtmlClasses);
  observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});

const modes = [
  { value: 'light', Icon: Sun, label: 'Light' },
  { value: 'dark', Icon: Moon, label: 'Dark' },
  { value: 'system', Icon: Monitor, label: 'System' },
] as const;

const themes = [
  {
    value: 'default',
    Icon: Sparkles,
    name: 'Default',
    desc: 'Cool blues & neutrals',
    colors: { primary: 'hsl(221, 83%, 53%)', surface: 'hsl(0 0% 100%)', text: 'hsl(0 0% 9%)' },
  },
  {
    value: 'ocean',
    Icon: Droplet,
    name: 'Ocean',
    desc: 'Vibrant teals & blues',
    colors: {
      primary: 'hsl(185, 80%, 42%)',
      surface: 'hsl(200 25% 98%)',
      text: 'hsl(200 20% 12%)',
    },
  },
  {
    value: 'forest',
    Icon: Trees,
    name: 'Forest',
    desc: 'Earthy greens',
    colors: {
      primary: 'hsl(145, 65%, 40%)',
      surface: 'hsl(140 15% 98%)',
      text: 'hsl(140 25% 10%)',
    },
  },
  {
    value: 'sunset',
    Icon: SunsetIcon,
    name: 'Sunset',
    desc: 'Warm purples & oranges',
    colors: { primary: 'hsl(265, 70%, 52%)', surface: 'hsl(25 30% 98%)', text: 'hsl(25 20% 10%)' },
  },
  {
    value: 'mono',
    Icon: Circle,
    name: 'Monochrome',
    desc: 'Classic black & white',
    colors: { primary: 'hsl(0 0% 10%)', surface: 'hsl(0 0% 100%)', text: 'hsl(0 0% 0%)' },
  },
  {
    value: 'high-contrast',
    Icon: Contrast,
    name: 'High Contrast',
    desc: 'Maximum visibility (WCAG AAA)',
    colors: { primary: 'hsl(220, 100%, 40%)', surface: 'hsl(0 0% 100%)', text: 'hsl(0 0% 0%)' },
  },
] as const;
</script>

<template>
  <div class="space-y-8">
    <!-- Mode Selection (Light/Dark/System) -->
    <div>
      <h3 class="mb-3 text-sm font-semibold text-text">{{ _('Mode') }}</h3>
      <div class="inline-flex gap-1 rounded-lg border border-border-subtle bg-surface-sunken p-1">
        <button
          v-for="{ value, Icon, label } in modes"
          :key="value"
          @click="updateMode(value)"
          :class="[
            'flex min-h-[44px] items-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition-all focus:ring-2 focus:ring-focus-ring focus:ring-offset-2 focus:outline-0 @md:min-h-[36px]',
            mode === value
              ? 'bg-interactive text-interactive-foreground shadow-md'
              : 'text-text-muted hover:bg-surface-interactive-hover hover:text-text',
          ]"
        >
          <component :is="Icon" class="h-4 w-4" />
          <span>{{ _(label) }}</span>
        </button>
      </div>
    </div>

    <!-- Color Theme Selection -->
    <div>
      <h3 class="mb-3 text-sm font-semibold text-text">{{ _('Color Theme') }}</h3>
      <div class="grid gap-3 @md:grid-cols-2 @xl:grid-cols-3">
        <button
          v-for="theme in themes"
          :key="theme.value"
          @click="updateColorTheme(theme.value)"
          :class="[
            'group relative flex min-h-[44px] items-center gap-3 rounded-xl border-2 p-3 text-left transition-all focus:ring-2 focus:ring-focus-ring focus:ring-offset-2 focus:outline-0',
            colorTheme === theme.value
              ? 'border-interactive shadow-md'
              : 'border-border bg-surface hover:border-border-strong hover:shadow-sm',
          ]"
          :style="
            colorTheme === theme.value ? { backgroundColor: theme.colors.primary + '08' } : {}
          "
        >
          <!-- Theme Icon/Preview -->
          <div
            class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg"
            :style="{
              backgroundColor: theme.colors.primary + '15',
              color: theme.colors.primary,
            }"
            :title="_(theme.name) + ' - ' + _(theme.desc)"
          >
            <component v-if="theme.Icon" :is="theme.Icon" class="h-5 w-5" />
            <div v-else class="text-sm font-bold">{{ theme.name[0] }}</div>
          </div>

          <!-- Theme Info -->
          <div class="min-w-0 flex-1">
            <div class="truncate text-sm font-semibold text-text" :title="_(theme.name)">
              {{ _(theme.name) }}
            </div>
            <div class="truncate text-xs text-text-subtle" :title="_(theme.desc)">
              {{ _(theme.desc) }}
            </div>
          </div>
          <!-- Selected Indicator -->
          <div
            v-if="colorTheme === theme.value"
            class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full text-interactive"
          >
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 16 16">
              <path
                d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z"
              />
            </svg>
          </div>
        </button>
      </div>

      <p class="mt-4 text-xs text-text-muted">
        {{ _('Each theme adapts to both light and dark modes') }}
      </p>
    </div>
  </div>
</template>
