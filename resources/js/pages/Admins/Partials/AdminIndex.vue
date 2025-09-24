<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import AdminCard from './AdminCard.vue';

const props = defineProps<{
  admins: PaginatedUsers | (User[] & { data?: undefined });
}>();

const list = computed(() => props.admins.data ?? props.admins);
</script>

<template>
  <div class="flex flex-col gap-base rounded-xl bg-accent/2">
    <div class="mx-2 self-center rounded-xl px-6 text-lg text-white/70">
      {{ _('Admins') }}
    </div>

    <div class="mx-6 mb-wide flex flex-wrap justify-center-safe gap-2">
      <AdminCard v-for="admin in list" :key="admin.id" :admin="admin" />
    </div>
  </div>
</template>
