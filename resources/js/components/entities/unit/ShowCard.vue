<script setup lang="ts">
import { CardContent, EntityCard } from '@/components/card';
import UnitFooter from './shared/UnitFooter.vue';
import UnitHeader from './shared/UnitHeader.vue';

defineProps<{
  unit: ApiResource<Unit>;
}>();
</script>

<template>
  <EntityCard :resource="unit" entity="unit" type="show">
    <UnitHeader :unit />

    <CardContent>
      <!-- TODO -->
    </CardContent>

    <UnitFooter :unit />
  </EntityCard>
</template>
