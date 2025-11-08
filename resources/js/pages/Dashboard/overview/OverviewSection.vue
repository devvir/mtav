<script setup lang="ts">
// Copilot - pending review
import { User, Building2, Layers, Shield, Calendar, Image as ImageIcon, UsersRound } from 'lucide-vue-next';
import StatCard from '@/components/dashboard/StatCard.vue';
import { _ } from '@/composables/useTranslations';

defineProps<{
  stats: {
    families?: number;
    members?: number;
    units?: number;
    unit_types?: number;
    admins?: number;
    media?: number;
    events?: number;
  };
}>();
</script>

<template>
  <section>
    <h2 class="mb-3 text-lg font-semibold">{{ _('Overview') }}</h2>
    <div class="grid gap-4 @sm:grid-cols-2 @2xl:grid-cols-4 @4xl:grid-cols-7">
      <StatCard :title="_('Families')" :value="stats.families" :icon="UsersRound" href="/families" />
      <StatCard :title="_('Members')" :value="stats.members" :icon="User" href="/members" />
      <StatCard :title="_('Admins')" :value="stats.admins" :icon="Shield" />
      <StatCard :title="_('Units')" :value="stats.units" :icon="Building2" href="/units" />
      <StatCard :title="_('Unit Types')" :value="stats.unit_types" :icon="Layers" href="/unit-types" />
      <StatCard :title="_('Media')" :value="stats.media" :icon="ImageIcon" />
      <StatCard :title="_('Events')" :value="stats.events" :icon="Calendar" />
    </div>
  </section>
</template>
