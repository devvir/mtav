<!-- Copilot - Pending review -->
<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { Sparkles } from 'lucide-vue-next';
</script>

<template>
  <div class="flex w-full flex-1 items-center justify-center">
    <div class="max-w-lg space-y-4 p-8 text-center">
      <div class="relative">
        <div
          class="mx-auto flex size-20 animate-pulse items-center justify-center rounded-full bg-primary/10"
        >
          <Sparkles class="size-10 text-primary" />
        </div>
        <div class="absolute inset-0 flex items-center justify-center">
          <div
            class="size-24 animate-spin rounded-full border-2 border-primary/20 border-t-primary"
          />
        </div>
      </div>

      <div class="space-y-2">
        <h3 class="text-xl font-semibold text-primary">
          {{ _('Lottery Magic in Progress') }}
        </h3>

        <p class="text-sm leading-relaxed text-text-muted">
          {{ _("The housing units are being assigned to families. This won't take long!") }}
        </p>
      </div>
    </div>
  </div>
</template>
