<script setup lang="ts">
// Copilot - pending review
import { Calendar } from 'lucide-vue-next';
import SectionHeader from '../shared/SectionHeader.vue';
import { _ } from '@/composables/useTranslations';
</script>

<template>
  <section>
    <SectionHeader :title="_('Upcoming Events')" />
    <div class="flex h-64 items-center justify-center rounded-lg">
      <div class="text-center text-sm text-text-muted">
        <Calendar class="mx-auto mb-2 h-8 w-8 opacity-50" />
        <p>{{ _('No events scheduled') }}</p>
      </div>
    </div>
  </section>
</template>
