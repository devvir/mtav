<script setup lang="ts">
import {
  EntityCard,
  CardContent,
  CardHeader,
  ContentHighlight,
  ContentLine,
} from '@/components/card';
import { _ } from '@/composables/useTranslations';
import AdminCardFooter from './AdminCardFooter.vue';

defineProps<{
  admin: ApiResource<Admin>;
}>();
</script>

<template>
  <EntityCard :resource="admin" entity="admin" type="show">
    <CardHeader :title="admin.name" :kicker="_('Admin')" avatar="xl" />

    <CardContent>
      <!-- Contact Information -->
      <div class="space-y-2">
        <ContentLine :label="_('Email')" :value="admin.email" />
        <ContentLine :label="_('Phone')" :value="admin.phone" />
      </div>

      <!-- About Section -->
      <ContentHighlight :title="_('About me')" :fallback-text="_('Nothing written yet')">
        {{ admin.about }}
      </ContentHighlight>
    </CardContent>

    <AdminCardFooter :admin />
  </EntityCard>
</template>
