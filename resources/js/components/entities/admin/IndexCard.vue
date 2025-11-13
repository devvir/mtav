<script setup lang="ts">
import { Card, CardHeader, CardFooter, HeaderSub } from '@/components/card';
import { _ } from '@/composables/useTranslations';

defineProps<{
  admin: ApiResource<Admin>;
}>();
</script>

<template>
  <Card :resource="admin" entity="admin" type="index">
    <CardHeader :title="admin.name" avatar="md">
      <HeaderSub :title="admin.email">{{ admin.email }}</HeaderSub>
    </CardHeader>

    <CardFooter />
  </Card>
</template>