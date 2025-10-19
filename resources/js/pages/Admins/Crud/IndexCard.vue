<script setup lang="ts">
import Card from '@/components/shared/Card.vue';
import CallToAction from '@/components/ui/button/CallToAction.vue';
import { _ } from '@/composables/useTranslations';

defineProps<{
  admin: Admin;
}>();
</script>

<template>
  <Card>
    <Link :href="route('users.show', admin.id)">
      <div class="flex items-center-safe justify-between gap-8">
        <div class="flex justify-start gap-5">
          <img :src="admin.avatar" alt="avatar" />
          <div class="flex flex-col items-start justify-center-safe" :title="admin.name">
            <div class="truncate text-xl">{{ admin.name }}</div>
            <div class="truncate text-xs">{{ admin.email }}</div>
          </div>
        </div>

        <CallToAction variant="default" :href="route('admins.contact', admin.id)">
          {{ _('Contact') }}
        </CallToAction>
      </div>
    </Link>
  </Card>
</template>
