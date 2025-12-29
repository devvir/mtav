<script setup lang="ts">
import { EntityCard, CardContent, CardFooter, CardHeader } from '@/components/card';
import { _ } from '@/composables/useTranslations';
import { Home, UsersRound } from 'lucide-vue-next';

defineProps<{
  unit_type: ApiResource<UnitType>;
}>();
</script>

<template>
  <EntityCard :resource="unit_type" entity="unit_type" type="index">
    <CardHeader :title="unit_type.name" :kicker="unit_type.description" />

    <CardContent>
      <!-- Stats Grid -->
      <div class="grid grid-cols-2 gap-2 text-sm">
        <!-- Units -->
        <div class="flex items-center gap-2 text-text-muted">
          <Home class="h-4 w-4 text-orange-500" />
          <span class="font-medium">{{ unit_type.units_count || 0 }}</span>
          <span>{{ unit_type.units_count === 1 ? _('Unit') : _('Units') }}</span>
        </div>

        <!-- Families -->
        <div class="flex items-center gap-2 text-text-muted">
          <UsersRound class="h-4 w-4 text-blue-500" />
          <span class="font-medium">{{ unit_type.families_count || 0 }}</span>
          <span>{{ unit_type.families_count === 1 ? _('Family') : _('Families') }}</span>
        </div>
      </div>
    </CardContent>

    <CardFooter />
  </EntityCard>
</template>
