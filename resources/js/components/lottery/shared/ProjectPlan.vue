<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { Card, CardContent, CardHeader } from '@/components/card';
import { Plan } from '@/components/projectplan';

defineProps<{
  plan: Plan;
}>();
</script>

<template>
  <Card>
    <CardHeader :title="_('Project Plan View')">
      <p class="text-sm">
        {{ _('Interactive Project Layout Plan') }}
      </p>
    </CardHeader>

    <CardContent>
      <Plan :plan class="w-full mx-auto" />
    </CardContent>
  </Card>
</template>