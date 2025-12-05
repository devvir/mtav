<!-- Copilot - Pending review -->
<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { Sparkles } from 'lucide-vue-next';
</script>

<template>
  <div class="flex-1 w-full flex items-center justify-center">
    <div class="text-center space-y-4 p-8 max-w-lg">
      <div class="relative">
        <div class="flex items-center justify-center mx-auto size-20 rounded-full bg-primary/10 animate-pulse">
          <Sparkles class="size-10 text-primary" />
        </div>
        <div class="absolute inset-0 flex items-center justify-center">
          <div class="size-24 rounded-full border-2 border-primary/20 border-t-primary animate-spin" />
        </div>
      </div>

      <div class="space-y-2">
        <h3 class="text-xl font-semibold text-primary">
          {{ _('Lottery Magic in Progress') }}
        </h3>

        <p class="text-sm text-text-muted leading-relaxed">
          {{ _('The housing units are being assigned to families. This won\'t take long!') }}
        </p>
      </div>
    </div>
  </div>
</template>
