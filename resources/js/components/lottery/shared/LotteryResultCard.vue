<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { Card, CardContent } from '@/components/card';
import { Home } from 'lucide-vue-next';

defineProps<{
  family: ApiResource<Family>;
}>();
</script>

<template>
  <Card class="overflow-hidden *:p-3">
    <CardContent class="flex-row p-2 items-stretch gap-2.5">
      <!-- House Icon -->
      <div class="size-9 shrink-0 rounded-lg bg-green-700/5 dark:bg-green-300/2 flex items-center justify-center">
        <Home class="size-4 text-green-900" />
      </div>

      <!-- Family and Unit Info -->
      <div class="flex-1 min-w-0 flex flex-col justify-center gap-0.5">
        <h3 class="font-semibold text-sm text-text truncate leading-tight max-w-[200px]" :title="family.name">
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
