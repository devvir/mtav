<script setup lang="ts">
import SkeletonCard from './SkeletonCard.vue';
</script>

<template>
  <!-- Overview skeleton -->
  <section>
    <div class="mb-3 h-7 w-32 animate-pulse rounded bg-surface-sunken" />
    <div class="grid gap-4 @sm:grid-cols-2 @2xl:grid-cols-4 @4xl:grid-cols-7">
      <SkeletonCard v-for="i in 7" :key="i" height="h-24" />
    </div>
  </section>

  <!-- Gallery and Events side by side -->
  <div class="grid gap-6 @4xl:grid-cols-2">
    <div class="rounded-xl bg-card-elevated p-6 shadow-sm">
      <div class="space-y-3">
        <div class="h-6 w-32 animate-pulse rounded bg-surface-sunken" />
        <div class="h-64 animate-pulse rounded-lg bg-surface-sunken" />
      </div>
    </div>
    <div class="rounded-xl bg-card-elevated p-6 shadow-sm">
      <div class="space-y-3">
        <div class="h-6 w-32 animate-pulse rounded bg-surface-sunken" />
        <div class="grid gap-3">
          <SkeletonCard v-for="i in 2" :key="i" height="h-24" />
        </div>
      </div>
    </div>
  </div>

  <!-- Families/Admins and Members side by side -->
  <div class="grid gap-6 @4xl:grid-cols-2">
    <!-- Left column: Families + Admins -->
    <div class="flex flex-col gap-6 rounded-xl bg-card-elevated p-6 shadow-sm">
      <div class="space-y-3">
        <div class="h-6 w-32 animate-pulse rounded bg-surface-sunken" />
        <div class="grid gap-3 @md:grid-cols-2">
          <SkeletonCard v-for="i in 4" :key="i" />
        </div>
      </div>
      <div class="space-y-3">
        <div class="h-6 w-32 animate-pulse rounded bg-surface-sunken" />
        <div class="grid gap-3 @md:grid-cols-2">
          <SkeletonCard v-for="i in 4" :key="i" />
        </div>
      </div>
    </div>

    <!-- Right column: Members -->
    <div class="rounded-xl bg-card-elevated p-6 shadow-sm">
      <div class="space-y-3">
        <div class="h-6 w-32 animate-pulse rounded bg-surface-sunken" />
        <div class="grid gap-4 @md:grid-cols-2">
          <SkeletonCard v-for="i in 10" :key="i" />
        </div>
      </div>
    </div>
  </div>

  <!-- Units by Type -->
  <div class="rounded-xl bg-card-elevated p-6 shadow-sm">
    <div class="space-y-4">
      <div class="h-6 w-32 animate-pulse rounded bg-surface-sunken" />
      <div class="grid gap-4 @2xl:grid-cols-2">
        <SkeletonCard v-for="i in 4" :key="i" height="h-40" />
      </div>
    </div>
  </div>
</template>
