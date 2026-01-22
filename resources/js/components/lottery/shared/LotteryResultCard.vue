<script setup lang="ts">
import { Card, CardContent } from '@/components/card';
import { _ } from '@/composables/useTranslations';
import { Home } from 'lucide-vue-next';

defineProps<{
  family: ApiResource<Family>;
}>();
</script>

<template>
  <Card class="overflow-hidden *:p-3">
    <CardContent class="flex-row items-stretch gap-2.5 p-2">
      <!-- House Icon -->
      <div
        class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-green-700/5 dark:bg-green-300/2"
      >
        <Home class="size-4 text-green-900" />
      </div>

      <!-- Family and Unit Info -->
      <div class="flex min-w-0 flex-1 flex-col justify-center gap-0.5">
        <h3
          class="max-w-[200px] truncate text-sm leading-tight font-semibold text-text"
          :title="family.name"
        >
          {{ family.name }}
        </h3>
        <p v-if="family.unit" class="text-xs text-text-subtle">
          {{ family.unit.identifier }}
        </p>
        <p v-else class="text-xs text-text-muted italic">
          {{ _('No assignment') }}
        </p>
      </div>
    </CardContent>
  </Card>
</template>
