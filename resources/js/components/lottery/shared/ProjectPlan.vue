<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { Card, CardHeader } from '@/components/card';
import { Plan } from '@/components/plans';

defineProps<{
  plan: Plan;
}>();
</script>

<template>
  <Card class="h-full flex flex-col max-w-auto">
    <CardHeader :title="_('Project Plan View')">
      <p class="text-sm">
        {{ _('Interactive Project Layout Plan') }}
      </p>
    </CardHeader>

    <Plan :plan class="w-full mx-auto" />
  </Card>
</template>