<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { FileText } from 'lucide-vue-next';

defineProps<{
  message?: string;
}>();
</script>

<template>
  <section class="flex size-full flex-col items-center justify-center py-16 text-center space-y-4">
    <div class="rounded-full bg-muted/50 p-6">
      <FileText class="size-12 text-muted-foreground" stroke-width="1.5" />
    </div>

    <div class="space-y-2">
      <h3 class="text-lg font-medium">
        {{ message || _('No items to display') }}
      </h3>
      <p class="text-sm text-muted-foreground">
        {{ _('Check back later as the Project grows!') }}
      </p>
    </div>
  </section>
</template>