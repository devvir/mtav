<script setup lang="ts">
import { Card, CardHeader, CardFooter, HeaderSub, FooterButton, CreatedMeta } from '@/components/card';
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

    <CardFooter class="flex items-center justify-between">
      <CreatedMeta />

      <FooterButton :href="route('contact', admin.id)">
        {{ _('Contact') }}
      </FooterButton>
    </CardFooter>
  </Card>
</template>