<script setup lang="ts">
import { _ } from '@/composables/useTranslations';

const props = withDefaults(
  defineProps<{
    q?: string;
    autofocus?: boolean;
  }>(),
  { q: '', autofocus: true },
);

const search = ref<string>(props.q ?? '');

watchDebounced(search, (q?: string) => router.reload({ data: { q: q ?? '' } }), {
  debounce: 300,
  maxWait: 1000,
});
</script>

<template>
  <div class="flex flex-col justify-between gap-wide lg:flex-row lg:items-center">
    <slot name="left" />

    <div class="flex-1">
      <input
        type="search"
        :autofocus="autofocus"
        v-model.trim="search"
        class="border-top-blue-100/80 w-full rounded-xl border-1 bg-foreground/4 px-6 py-2 text-lg text-foreground placeholder-current/50 shadow-accent-foreground/25 outline-0 focus:placeholder-transparent focus:shadow hocus:bg-accent/8"
        :placeholder="_('Search...')"
      />
    </div>

    <slot name="right" />
  </div>
</template>
