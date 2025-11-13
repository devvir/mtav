<script setup lang="ts">
import { Badge, BinaryBadge } from '@/components/badge';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/card';
import { _ } from '@/composables/useTranslations';

defineProps<{
  unit: ApiResource<Unit>;
}>();
</script>

<template>
  <Card :resource="unit" entity="unit" type="show">
    <CardHeader :title="unit.identifier" />

    <CardContent>
      <div class="space-y-2">
        <div class="flex flex-wrap gap-2">
          <BinaryBadge :when="unit.family" :then="_('Assigned')" :else="_('Available')" />
          <Badge variant="outline">Type {{ unit.type.id }}</Badge>
          <Badge variant="outline">Project {{ unit.project.id }}</Badge>
        </div>

        <p class="text-sm">
          {{ unit.family ? `${_('Assigned to Family:')} ${unit.family.id}` : _('Available for assignment') }}
        </p>
      </div>
    </CardContent>

    <CardFooter />
  </Card>
</template>