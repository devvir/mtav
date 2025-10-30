<script setup lang="ts">
// Copilot - pending review
import { UsersRound } from 'lucide-vue-next';
import SectionHeader from '../shared/SectionHeader.vue';
import FamilyCard from './FamilyCard.vue';
import { _ } from '@/composables/useTranslations';

defineProps<{
  families: any[];
  totalCount: number;
}>();
</script>

<template>
  <section class="flex-1">
    <SectionHeader
      :title="_('Families')"
      :view-all-href="families.length > 0 ? '/families' : undefined"
      :view-all-text="families.length > 0 ? `${_('View all')} (${totalCount})` : undefined"
    />
    <div v-if="families.length > 0" class="grid gap-4 @md:grid-cols-2">
      <FamilyCard v-for="family in families" :key="family.id" :family="family" />
    </div>
    <div v-else class="flex h-32 items-center justify-center rounded-lg">
      <div class="text-center text-sm text-muted-foreground">
        <UsersRound class="mx-auto mb-2 h-8 w-8 opacity-50" />
        <p>{{ _('No families yet') }}</p>
      </div>
    </div>
  </section>
</template>
