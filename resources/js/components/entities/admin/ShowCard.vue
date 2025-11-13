<script setup lang="ts">
import { Card, CardHeader, CardContent, CardFooter, ContentHighlight, ContentLine, CreatedMeta, FooterButton } from '@/components/card';
import { _ } from '@/composables/useTranslations';

defineProps<{
  admin: ApiResource<Admin>;
}>();
</script>

<template>
  <Card :resource="admin" entity="admin" type="show">
    <CardHeader :title="admin.name" :kicker="_('Admin')" avatar="lg" />

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

    <CardFooter class="flex items-center justify-between">
      <CreatedMeta />

      <FooterButton :href="route('contact', admin.id)">
        {{ _('Contact') }}
      </FooterButton>
    </CardFooter>
  </Card>
</template>