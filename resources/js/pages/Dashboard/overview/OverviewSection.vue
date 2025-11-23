<script setup lang="ts">
import StatCard from '@/components/dashboard/StatCard.vue';
import { _ } from '@/composables/useTranslations';
import { Building2Icon, Calendar, FilesIcon, Image as ImageIcon, Shield, User, UsersRound } from 'lucide-vue-next';

defineProps<{
  stats: Project;
}>();
</script>

<template>
  <section>
    <h2 class="mb-3 text-lg font-semibold">{{ _('Overview') }}</h2>

    <div class="grid gap-4 @sm:grid-cols-2 @2xl:grid-cols-4 @4xl:grid-cols-7">
      <StatCard :title="_('Units')" :value="stats.units_count" :icon="Building2Icon" linkToRoute="units.index" />
      <StatCard :title="_('Families')" :value="stats.families_count" :icon="UsersRound" linkToRoute="families.index" />
      <StatCard :title="_('Members')" :value="stats.members_count" :icon="User" linkToRoute="members.index" />
      <StatCard :title="_('Admins')" :value="stats.admins_count" :icon="Shield" linkToRoute="admins.index" />
      <StatCard :title="_('Multimedia')" :value="stats.visual_media_count" :icon="ImageIcon" linkToRoute="gallery" />
      <StatCard :title="_('Documents')" :value="stats.documents_count" :icon="FilesIcon" linkToRoute="documents.index" />
      <StatCard :title="_('Events')" :value="stats.events_count" :icon="Calendar" linkToRoute="events.index" />
    </div>
  </section>
</template>
