<script setup lang="ts">
import { EntityCard } from '@/components/card';
import UnitFooter from './shared/UnitFooter.vue';
import UnitHeader from './shared/UnitHeader.vue';

defineProps<{
  unit: ApiResource<Unit>;
}>();
</script>

<template>
  <EntityCard :resource="unit" entity="unit" type="index">
    <UnitHeader :unit />
    <UnitFooter :unit />
  </EntityCard>
</template>
