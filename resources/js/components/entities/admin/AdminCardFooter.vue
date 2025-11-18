<script setup lang="ts">
import { CardFooter, CreatedMeta, FooterButton } from '@/components/card';
import { _ } from '@/composables/useTranslations';

defineProps<{
  admin: ApiResource<Admin>;
}>();
</script>

<template>
  <CardFooter class="flex items-center justify-between">
    <CreatedMeta />

    <FooterButton :href="route('contact', admin.id)">
      {{ _('Contact') }}
    </FooterButton>
  </CardFooter>
</template>
