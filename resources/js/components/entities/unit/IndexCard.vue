<script setup lang="ts">
import { Card } from '@/components/card';
import UnitFooter from './shared/UnitFooter.vue';
import UnitHeader from './shared/UnitHeader.vue';

defineProps<{
  unit: ApiResource<Unit>;
}>();
</script>

<template>
  <Card :resource="unit" entity="unit" type="index">
    <UnitHeader :unit />
    <UnitFooter :unit />
  </Card>
</template>
