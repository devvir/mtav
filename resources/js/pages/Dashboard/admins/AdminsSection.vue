<script setup lang="ts">
// Copilot - Pending review
import { can } from '@/composables/useAuth';
import { _ } from '@/composables/useTranslations';
import { Shield } from 'lucide-vue-next';
import SectionHeader from '../shared/SectionHeader.vue';
import AdminCard from './AdminCard.vue';

defineProps<{
  admins: Admin[];
  totalCount: number;
}>();
</script>

<template>
  <section>
    <SectionHeader
      :title="_('Admins')"
      view-all-href="admins.index"
      :view-all-text="totalCount > admins.length ? `${_('View all')} (${totalCount})` : undefined"
      create-href="admins.create"
      :create-if="can.create('admins')"
      :create-label="_('Create Admin')"
    />
    <div v-if="admins.length > 0" class="grid gap-4 @md:grid-cols-2">
      <AdminCard v-for="admin in admins" :key="admin.id" :admin="admin" />
    </div>
    <div v-else class="flex h-32 items-center justify-center rounded-lg">
      <div class="text-center text-sm text-text-muted">
        <Shield class="mx-auto mb-2 h-8 w-8 opacity-50" />
        <p>{{ _('No Admins yet') }}</p>
      </div>
    </div>
  </section>
</template>
