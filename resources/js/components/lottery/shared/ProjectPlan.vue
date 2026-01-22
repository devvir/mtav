<script setup lang="ts">
import { Card, CardContent, CardHeader } from '@/components/card';
import { Plan } from '@/components/projectplan';
import { Button } from '@/components/ui/button';
import { _ } from '@/composables/useTranslations';

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
      <Plan :plan class="mx-auto w-full" />
    </CardContent>
  </Card>
</template>
