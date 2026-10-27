<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

/**
 * The top of a page: optional way back, title, short description, actions.
 */
defineProps<{ title: string; description?: string; back?: { href: string; label: string } }>();
</script>

<template>
  <Head :title />
  <header class="mb-6 space-y-3">
    <Link
      v-if="back"
      :href="back.href"
      class="inline-flex min-h-11 items-center gap-2 text-sm font-medium text-text-muted hover:text-text"
    >
      <ArrowLeft class="size-4" /> {{ back.label }}
    </Link>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div class="min-w-0">
        <div class="flex items-center gap-1">
          <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">{{ title }}</h1>
          <slot name="info" />
        </div>
        <p v-if="description" class="mt-1 max-w-2xl text-text-muted">{{ description }}</p>
      </div>
      <div v-if="$slots.actions" class="flex flex-wrap gap-2"><slot name="actions" /></div>
    </div>
  </header>
</template>
