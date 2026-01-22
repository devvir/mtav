<script setup lang="ts">
import { CardHeader } from '@/components/card';
import { Home } from 'lucide-vue-next';

defineProps<{
  unit: ApiResource<Unit>;
}>();
</script>

<template>
  <CardHeader :title="`${unit.identifier} (${unit.type?.description})`">
    <template #icon>
      <Home class="size-10 text-amber-600/60" />
    </template>
  </CardHeader>
</template>
