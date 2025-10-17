<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import IndexCard from './IndexCard.vue';

const props = defineProps<{
  admins: PaginatedUsers | (User[] & { data?: undefined });
}>();

const list = computed(() => props.admins.data ?? props.admins);
</script>

<template>
  <div class="flex flex-col gap-2 rounded-b-xl bg-accent/2">
    <div class="mx-2 px-6 text-lg text-white/20 underline underline-offset-4">
      {{ _('Admins') }}
    </div>

    <div class="mx-6 mb-wide space-y-3">
      <IndexCard v-for="admin in list" :key="admin.id" :admin="admin" class="w-full" />
    </div>
  </div>
</template>
