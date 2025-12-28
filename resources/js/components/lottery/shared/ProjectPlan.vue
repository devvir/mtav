<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { Card, CardContent, CardHeader } from '@/components/card';
import { Plan } from '@/components/projectplan';
import { Button } from '@/components/ui/button';

defineProps<{
  plan: Plan;
}>();
</script>

<template>
  <Card>
    <CardHeader :title="_('Project Plan View')">
      <div class="flex justify-between">
        {{ _('Project Layout Plan') }}

        <Button as-child size="sm">
          <Link :href="route('plans.edit', plan.id)">
            {{ _('Update') }}
          </Link>
        </Button>
      </div>
    </CardHeader>

    <CardContent>
      <Plan :plan class="w-full mx-auto" />
    </CardContent>
  </Card>
</template>