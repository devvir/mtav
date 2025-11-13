<script setup lang="ts">
import { BinaryBadge } from '@/components/badge';
import { Card, CardContent, CardHeader } from '@/components/card';

defineProps<{
  unit: ApiResource<Unit>;
}>();
</script>

<template>
  <Card :resource="unit" entity="unit" type="index">
    <CardHeader :title="unit.identifier" />

    <CardContent>
      <BinaryBadge :when="unit.family" :then="'Assigned'" :else="'Available'" />
      <span class="text-xs">Type {{ unit.type.id }}</span>
    </CardContent>
  </Card>
</template>
