<script setup lang="ts">
import { useTheme } from '@/state/useTheme';
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

const { mode, theme, setMode, setTheme } = useTheme();

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
  <div class="space-y-8 mt-8">
    <!-- Mode Selection (Light/Dark/System) -->
    <div class="py-1 max-xs:text-center">
      <h3 class="mb-2 text-sm font-semibold text-text max-xs:hidden">{{ _('Mode') }}</h3>
      <div class="inline-flex gap-2 rounded-lg border border-border-subtle bg-surface-sunken p-1">
        <button
          v-for="{ value, Icon, label } in modes"
          :key="value"
          @click="setMode(value)"
          :class="[
            'flex min-h-10 items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition-all focus:ring-2 focus:ring-focus-ring focus:ring-offset-2 focus:outline-0',
            mode === value
              ? 'bg-interactive text-interactive-foreground shadow-md'
              : 'text-text-muted hover:bg-surface-interactive-hover hover:text-text',
          ]"
        >
          <component :is="Icon" class="h-4 w-4" />
          <span class="max-xs:hidden">{{ _(label) }}</span>
        </button>
      </div>
    </div>

    <!-- Color Theme Selection -->
    <div>
      <h3 class="mb-2 text-sm font-semibold text-text">{{ _('Color Theme') }}</h3>
      <div class="grid gap-2 @md:grid-cols-2 @xl:grid-cols-3">
        <button
          v-for="option in themes"
          :key="option.value"
          @click="setTheme(option.value)"
          :class="[
            'group relative flex min-h-10 items-center gap-2.5 rounded-lg border-2 p-2 text-left transition-all focus:ring-2 focus:ring-focus-ring focus:ring-offset-2 focus:outline-0',
            theme === option.value
              ? 'border-interactive shadow-md'
              : 'border-border bg-surface hover:border-border-strong hover:shadow-sm',
          ]"
          :style="
            theme === option.value ? { backgroundColor: option.colors.primary + '08' } : {}
          "
        >
          <!-- Theme Icon/Preview -->
          <div
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md"
            :style="{
              backgroundColor: option.colors.primary + '15',
              color: option.colors.primary,
            }"
            :title="_(option.name) + ' - ' + _(option.desc)"
          >
            <component v-if="option.Icon" :is="option.Icon" class="h-4 w-4" />
            <div v-else class="text-xs font-bold">{{ option.name[0] }}</div>
          </div>

          <!-- Theme Info -->
          <div class="min-w-0 flex-1">
            <div class="truncate text-sm font-semibold text-text" :title="_(option.name)">
              {{ _(option.name) }}
            </div>
            <div class="truncate text-xs text-text-subtle" :title="_(option.desc)">
              {{ _(option.desc) }}
            </div>
          </div>
          <!-- Selected Indicator -->
          <div
            v-if="theme === option.value"
            class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-interactive"
          >
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 16 16">
              <path
                d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z"
              />
            </svg>
          </div>
        </button>
      </div>

      <p class="mt-3 text-xs text-text-muted">
        {{ _('Each theme adapts to both light and dark modes') }}
      </p>
    </div>
  </div>
</template>
