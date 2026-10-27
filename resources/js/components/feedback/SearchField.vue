<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { router } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';

/**
 * Filters the current page by a query (?q=…), as you type.
 */
const props = defineProps<{ modelValue?: string | null; placeholder?: string; param?: string }>();

const value = ref(props.modelValue ?? '');

watchDebounced(
  value,
  (q) => {
    const url = new URL(window.location.href);
    if (q) url.searchParams.set(props.param ?? 'q', q);
    else url.searchParams.delete(props.param ?? 'q');
    url.searchParams.delete('page');
    router.get(
      url.pathname + url.search,
      {},
      { preserveState: true, preserveScroll: true, replace: true },
    );
  },
  { debounce: 350 },
);
</script>

<template>
  <label class="relative block">
    <span class="sr-only">{{ placeholder ?? _('Search') }}</span>
    <Search
      class="pointer-events-none absolute top-1/2 left-3 size-5 -translate-y-1/2 text-text-subtle"
    />
    <input
      v-model="value"
      type="search"
      :placeholder="placeholder ?? _('Search')"
      class="block min-h-11 w-full rounded-lg border-2 border-border bg-surface py-2 pr-3 pl-10 text-base outline-none placeholder:text-text-subtle focus-visible:border-border-interactive focus-visible:ring-2 focus-visible:ring-focus-ring/40"
    />
  </label>
</template>
