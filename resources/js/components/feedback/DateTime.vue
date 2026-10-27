<script setup lang="ts">
import { languageTag as lang } from '@/composables/useTranslations';

/**
 * A date (and time), formatted for the reader's language; the full timestamp on hover.
 */
const props = withDefaults(
  defineProps<{ value: string | null; time?: boolean; relative?: boolean }>(),
  { time: true },
);

const date = computed(() => (props.value ? new Date(props.value) : null));

const text = computed(() => {
  if (!date.value) return '';

  if (props.relative) {
    const seconds = (date.value.getTime() - Date.now()) / 1000;
    const units: [Intl.RelativeTimeFormatUnit, number][] = [
      ['year', 31536000],
      ['month', 2592000],
      ['week', 604800],
      ['day', 86400],
      ['hour', 3600],
      ['minute', 60],
    ];
    const [unit, size] = units.find(([, s]) => Math.abs(seconds) >= s) ?? ['second', 1];
    return new Intl.RelativeTimeFormat(lang.value, { numeric: 'auto' }).format(
      Math.round(seconds / size),
      unit,
    );
  }

  return new Intl.DateTimeFormat(lang.value, {
    dateStyle: 'medium',
    ...(props.time ? { timeStyle: 'short' } : {}),
  }).format(date.value);
});
</script>

<template>
  <time v-if="date" :datetime="value!" :title="date.toLocaleString(lang)">{{ text }}</time>
</template>
