<script setup lang="ts">
// Copilot - pending review
import { _ } from '@/composables/useTranslations';
import { Building2 } from 'lucide-vue-next';
import SectionHeader from '../shared/SectionHeader.vue';
import UnitTypeCard from './UnitTypeCard.vue';

defineProps<{
  unitTypes: UnitType[];
}>();
</script>

<template>
  <section>
    <SectionHeader :title="_('Project Units')" />
    <div v-if="unitTypes.length > 0" class="grid gap-4 @2xl:grid-cols-2">
      <UnitTypeCard v-for="unitType in unitTypes" :key="unitType.id" :unitType="unitType" />
    </div>
    <div v-else class="flex h-40 items-center justify-center rounded-lg">
      <div class="text-center text-sm text-text-muted">
        <Building2 class="mx-auto mb-2 h-8 w-8 opacity-50" />
        <p>{{ _('No units yet') }}</p>
      </div>
    </div>
  </section>
</template>
