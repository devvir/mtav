<script setup lang="ts">
import { cn } from '@/lib/utils';
import { CreatedMeta } from '.';
</script>

<template>
  <footer :class="cn('block text-right text-xs text-text-subtle', $attrs.class, 'mt-auto')">
    <slot>
      <CreatedMeta />
    </slot>
  </footer>
</template>
