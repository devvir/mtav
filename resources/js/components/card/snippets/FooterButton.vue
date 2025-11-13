<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import type { Method } from 'axios';

defineProps<{
  href: string;
  method?: Method;
}>();
</script>

<template>
  <Button variant="outline" size="sm" as-child class="-my-2">
    <Link :href :method @click.stop>
      <slot />
    </Link>
  </Button>
</template>