<script setup lang="ts">
import { _ } from '@/composables/useTranslations';

defineProps<{
  title: string;
  list: string[];
}>();
</script>

<template>
  <div class="space-y-4 rounded-xl border border-surface-sunken/50 bg-surface-sunken/30 px-5 py-4">
    <div class="flex items-center gap-2.5">
      <div class="h-px flex-1 bg-linear-to-r from-transparent via-primary/20 to-transparent"></div>
      <h2 class="text-xs font-semibold tracking-wider text-foreground/60 uppercase">
        {{ _(title) }}
      </h2>
      <div class="h-px flex-1 bg-linear-to-r from-transparent via-primary/20 to-transparent"></div>
    </div>

    <ul class="list-none space-y-3.5 text-sm text-foreground/75">
      <li
        v-for="(item, index) in list"
        :key="index"
        class="group flex items-start gap-3 leading-relaxed"
      >
        <span
          class="mt-1.5 inline-block size-1.5 shrink-0 rounded-full bg-primary/50 transition-colors duration-200 group-hover:bg-primary/70"
          aria-hidden="true"
        ></span>
        <span class="flex-1">{{ _(item) }}</span>
      </li>
    </ul>
  </div>
</template>
