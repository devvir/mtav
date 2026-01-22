<script setup lang="ts">
// Copilot - Pending review
import { can } from '@/composables/useAuth';
import { _ } from '@/composables/useTranslations';
import { User } from 'lucide-vue-next';
import SectionHeader from '../shared/SectionHeader.vue';
import MemberCard from './MemberCard.vue';

defineProps<{
  members: Member[];
  totalCount: number;
}>();
</script>

<template>
  <section>
    <SectionHeader
      :title="_('Recent Members')"
      view-all-href="members.index"
      :view-all-text="totalCount > members.length ? `${_('View all')} (${totalCount})` : undefined"
      create-href="members.create"
      :create-if="can.create('members')"
      :create-label="_('Create Member')"
    />
    <div v-if="members.length > 0" class="grid gap-4 @md:grid-cols-2">
      <MemberCard v-for="member in members" :key="member.id" :member="member" />
    </div>
    <div v-else class="flex h-64 items-center justify-center rounded-lg">
      <div class="text-center text-sm text-text-muted">
        <User class="mx-auto mb-2 h-8 w-8 opacity-50" />
        <p>{{ _('No members yet') }}</p>
      </div>
    </div>
  </section>
</template>
