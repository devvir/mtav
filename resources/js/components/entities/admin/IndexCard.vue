<script setup lang="ts">
import { EntityCard, CardHeader, HeaderSub } from '@/components/card';
import AdminCardFooter from './AdminCardFooter.vue';

defineProps<{
  admin: ApiResource<Admin>;
}>();
</script>

<template>
  <EntityCard :resource="admin" entity="admin" type="index" :dimmed="!admin.projects!.length">
    <CardHeader :title="admin.name" avatar="lg">
      <HeaderSub :title="admin.email">{{ admin.email }}</HeaderSub>
    </CardHeader>

    <AdminCardFooter :admin />
  </EntityCard>
</template>
