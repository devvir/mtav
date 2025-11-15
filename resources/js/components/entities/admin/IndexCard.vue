<script setup lang="ts">
import {
  Card,
  CardFooter,
  CardHeader,
  CreatedMeta,
  FooterButton,
  HeaderSub,
} from '@/components/card';
import { _ } from '@/composables/useTranslations';

defineProps<{
  admin: ApiResource<Admin>;
}>();
</script>

<template>
  <Card :resource="admin" entity="admin" type="index" :dimmed="!admin.projects!.length">
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
