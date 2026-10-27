<script setup lang="ts">
import PageHeader from '@/components/layout/PageHeader.vue';
import { useTheme } from '@/composables/useTheme';
import { _ } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';
import { Contrast, Monitor, Moon, Sun } from 'lucide-vue-next';

const { mode, theme, setMode, setTheme } = useTheme();

const modes = computed(() => [
  { value: 'light' as const, label: _('Light'), icon: Sun },
  { value: 'dark' as const, label: _('Dark'), icon: Moon },
  { value: 'system' as const, label: _('As my device'), icon: Monitor },
]);

const option = (active: boolean) =>
  cn(
    'flex min-h-14 items-center gap-3 rounded-xl border-2 px-4 font-medium transition',
    active
      ? 'border-border-interactive bg-selection/40'
      : 'border-border hover:border-border-strong',
  );
</script>

<template>
  <PageHeader :title="_('Appearance')" :description="_('Only on this device.')" />

  <section class="max-w-2xl space-y-8">
    <fieldset>
      <legend class="mb-3 font-semibold">{{ _('Light or dark') }}</legend>
      <div class="grid gap-3 sm:grid-cols-3">
        <button
          v-for="item in modes"
          :key="item.value"
          type="button"
          :class="option(mode === item.value)"
          :aria-pressed="mode === item.value"
          @click="setMode(item.value)"
        >
          <component :is="item.icon" class="size-5" /> {{ item.label }}
        </button>
      </div>
    </fieldset>

    <fieldset>
      <legend class="mb-3 font-semibold">{{ _('Contrast') }}</legend>
      <button
        type="button"
        :class="option(theme === 'high-contrast')"
        :aria-pressed="theme === 'high-contrast'"
        @click="setTheme(theme === 'high-contrast' ? 'default' : 'high-contrast')"
      >
        <Contrast class="size-5" /> {{ _('High contrast') }}
      </button>
    </fieldset>
  </section>
</template>
