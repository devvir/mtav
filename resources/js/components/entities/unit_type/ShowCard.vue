<script setup lang="ts">
import {EntityCard, CardContent, CardFooter, CardHeader, ContentGrid, CreatedMeta, StatBox } from '@/components/card';
import { Building2, Home, UsersRound } from 'lucide-vue-next';

defineProps<{
  unit_type: ApiResource<UnitType>;
}>();
</script>

<template>
  <EntityCard :resource="unit_type" entity="unit_type" type="show">
    <CardHeader :title="unit_type.description" :kicker="unit_type.name">
      <template #icon>
        <Building2 class="size-10" />
      </template>
    </CardHeader>

    <CardContent>
      <ContentGrid>
        <StatBox
          :icon="Home"
          :count="unit_type.units_count || 0"
          entity="unit"
          icon-color="text-orange-500"
          route="units.index"
        />

        <StatBox
          :icon="UsersRound"
          :count="unit_type.families_count || 0"
          entity="family"
          icon-color="text-blue-500"
          route="families.index"
        />
      </ContentGrid>
    </CardContent>

    <CardFooter>
      <CreatedMeta />
    </CardFooter>
  </EntityCard>
</template>
