<script setup lang="ts">
// Copilot - Pending review
import { can } from '@/composables/useAuth';
import { _ } from '@/composables/useTranslations';
import { UsersRound } from 'lucide-vue-next';
import SectionHeader from '../shared/SectionHeader.vue';
import FamilyCard from './FamilyCard.vue';

defineProps<{
  families: Family[];
  totalCount: number;
}>();
</script>

<template>
  <section class="flex-1">
    <SectionHeader
      :title="_('Families')"
      view-all-href="families.index"
      :view-all-text="totalCount > families.length ? `${_('View all')} (${totalCount})` : undefined"
      create-href="families.create"
      :create-if="can.create('families')"
      :create-label="_('Create Family')"
    />
    <div v-if="families.length > 0" class="grid gap-4 @md:grid-cols-2">
      <FamilyCard v-for="family in families" :key="family.id" :family="family" />
    </div>
    <div v-else class="flex h-32 items-center justify-center rounded-lg">
      <div class="text-center text-sm text-text-muted">
        <UsersRound class="mx-auto mb-2 h-8 w-8 opacity-50" />
        <p>{{ _('No Families yet') }}</p>
      </div>
    </div>
  </section>
</template>
