<script setup lang="ts">
import { _ } from '@/composables/useTranslations';

defineProps<{
  title: string;
  list: string[];
}>();
</script>

<template>
  <div class="rounded-xl bg-surface-sunken/30 px-5 py-4 space-y-4 border border-surface-sunken/50">
    <div class="flex items-center gap-2.5">
      <div class="h-px flex-1 bg-linear-to-r from-transparent via-primary/20 to-transparent"></div>
      <h2 class="font-semibold text-foreground/60 uppercase tracking-wider text-xs">
        {{ _(title) }}
      </h2>
      <div class="h-px flex-1 bg-linear-to-r from-transparent via-primary/20 to-transparent"></div>
    </div>

    <ul class="space-y-3.5 text-sm text-foreground/75 list-none">
      <li v-for="(item, index) in list" :key="index" class="flex items-start gap-3 leading-relaxed group">
        <span
          class="mt-1.5 inline-block size-1.5 shrink-0 rounded-full bg-primary/50 group-hover:bg-primary/70 transition-colors duration-200"
          aria-hidden="true"
        ></span>
        <span class="flex-1">{{ _(item) }}</span>
      </li>
    </ul>
  </div>
</template>
