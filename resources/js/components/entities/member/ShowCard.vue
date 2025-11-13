<script setup lang="ts">
import {
  Card,
  CardContent,
  CardFooter,
  CardHeader,
  ContentHighlight,
  ContentLine,
} from '@/components/card';
import { _ } from '@/composables/useTranslations';

defineProps<{
  member: ApiResource<Member>;
}>();
</script>

<template>
  <Card :resource="member" entity="member" type="show">
    <CardHeader :title="member.name" avatar="lg" />

    <CardContent>
      <!-- Contact Information -->
      <div class="space-y-2 text-sm">
        <ContentLine :label="_('Email')" :value="member.email" />
        <ContentLine :label="_('Phone')" :value="member.phone" />
        <ContentLine :label="_('Family')" :value="member.family.name" />
      </div>

      <!-- About Section -->
      <ContentHighlight :title="_('About me')" :fallback-text="_('Nothing written yet')">
        {{ member.about }}
      </ContentHighlight>
    </CardContent>

    <CardFooter />
  </Card>
</template>
