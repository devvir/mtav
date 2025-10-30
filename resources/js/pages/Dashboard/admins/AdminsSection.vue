<script setup lang="ts">
// Copilot - pending review
import { Shield } from 'lucide-vue-next';
import SectionHeader from '../shared/SectionHeader.vue';
import AdminCard from './AdminCard.vue';
import { _ } from '@/composables/useTranslations';

defineProps<{
  admins: any[];
}>();
</script>

<template>
  <section>
    <SectionHeader :title="_('Administrators')" />
    <div v-if="admins.length > 0" class="grid gap-4 @md:grid-cols-2">
      <AdminCard v-for="admin in admins" :key="admin.id" :admin="admin" />
    </div>
    <div v-else class="flex h-32 items-center justify-center rounded-lg">
      <div class="text-center text-sm text-muted-foreground">
        <Shield class="mx-auto mb-2 h-8 w-8 opacity-50" />
        <p>{{ _('No administrators yet') }}</p>
      </div>
    </div>
  </section>
</template>
