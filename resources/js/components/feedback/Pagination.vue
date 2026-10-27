<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import type { Paginated } from '@/types/models';
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

defineProps<{ page: Paginated<unknown> }>();
</script>

<template>
  <nav
    v-if="page.meta.last_page > 1"
    class="flex items-center justify-between gap-4 pt-4"
    :aria-label="_('Pages')"
  >
    <p class="text-sm text-text-muted">
      {{
        _('{from}–{to} of {total}', {
          from: page.meta.from ?? 0,
          to: page.meta.to ?? 0,
          total: page.meta.total,
        })
      }}
    </p>
    <div class="flex gap-2">
      <Link
        :href="page.links.prev ?? ''"
        :class="[
          'flex size-11 items-center justify-center rounded-lg border-2 border-border',
          !page.links.prev && 'pointer-events-none opacity-40',
        ]"
        :aria-label="_('Previous page')"
        preserve-scroll
      >
        <ChevronLeft class="size-5" />
      </Link>
      <Link
        :href="page.links.next ?? ''"
        :class="[
          'flex size-11 items-center justify-center rounded-lg border-2 border-border',
          !page.links.next && 'pointer-events-none opacity-40',
        ]"
        :aria-label="_('Next page')"
        preserve-scroll
      >
        <ChevronRight class="size-5" />
      </Link>
    </div>
  </nav>
</template>
