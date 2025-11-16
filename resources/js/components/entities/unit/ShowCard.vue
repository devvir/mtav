<script setup lang="ts">
import { Badge, BinaryBadge } from '@/components/badge';
import { Card, CardContent } from '@/components/card';
import { _ } from '@/composables/useTranslations';
import UnitFooter from './shared/UnitFooter.vue';
import UnitHeader from './shared/UnitHeader.vue';

defineProps<{
  unit: ApiResource<Unit>;
}>();
</script>

<template>
  <Card :resource="unit" entity="unit" type="show">
    <UnitHeader :unit />

    <CardContent>
      TODO
    </CardContent>

    <UnitFooter :unit />
  </Card>
</template>
