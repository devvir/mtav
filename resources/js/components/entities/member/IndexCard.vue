<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { Card, CardFooter, CardHeader, HeaderSub } from '@/components/card';

defineProps<{
  member: ApiResource<Member>;
}>();
</script>

<template>
  <Card :resource="member" entity="member" type="index">
    <CardHeader :title="member.name" avatar="md">
      <HeaderSub :title="member.email">{{ member.email }}</HeaderSub>
    </CardHeader>

    <CardFooter />
  </Card>
</template>