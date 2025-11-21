<script setup lang="ts">
import { Deferred } from '@inertiajs/vue3';
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import {
  AdminsSection,
  EventsSection,
  FamiliesSection,
  GallerySection,
  MembersSection,
  OverviewSection,
  SkeletonCard,
  UnitsSection,
  WhileLoading,
} from '.';

const props = defineProps<{
  stats?: Project;
  admins?: Admin[];
  members?: Member[];
  families?: Family[];
  events?: Event[];
  media?: Media[];
  unitTypes?: UnitType[];
}>();

const visibleFamilies = computed<Family[]>(() => {
  if (!props.admins || !props.members || !props.families) return [];

  const adminRows = Math.ceil(props.admins.length / 2); // admins in 2 columns
  const memberRows = Math.ceil(props.members.length / 2); // members in 2 columns
  const availableRowsForFamilies = Math.max(0, memberRows - adminRows - 1);
  const maxFamiliesToShow = Math.min(props.families.length, availableRowsForFamilies * 2);

  return props.families.slice(0, maxFamiliesToShow) || [];
});
</script>

<template>
  <Head title="Project Dashboard" />

  <Breadcrumbs>
    <Breadcrumb route="dashboard" text="Project Dashboard" />
  </Breadcrumbs>

  <div class="flex h-full flex-1 flex-col gap-8 p-4">
    <Deferred :data="['stats', 'admins', 'members', 'families', 'events', 'media', 'unitTypes']">
      <template #fallback>
        <!-- Overview skeleton -->
        <section>
          <div class="mb-3 h-7 w-32 animate-pulse rounded bg-surface-sunken" />
          <div class="grid gap-4 @sm:grid-cols-2 @2xl:grid-cols-4 @4xl:grid-cols-7">
            <SkeletonCard v-for="i in 7" :key="i" height="h-24" />
          </div>
        </section>

        <WhileLoading />
      </template>

      <OverviewSection :stats="stats" />

      <!-- Gallery and Events side by side -->
      <div class="grid gap-6 @4xl:grid-cols-2">
        <div class="rounded-xl bg-card-elevated p-6 shadow-sm">
          <GallerySection :media />
        </div>
        <div class="rounded-xl bg-card-elevated p-6 shadow-sm">
          <EventsSection :events />
        </div>
      </div>

      <!-- Families/Admins and Members side by side -->
      <div class="grid gap-6 @4xl:grid-cols-2">
        <!-- Left column: Families + Admins -->
        <div class="flex flex-col gap-6 rounded-xl bg-card-elevated p-6 shadow-sm">
          <FamiliesSection :families="visibleFamilies" :total-count="stats?.families_count" />
          <AdminsSection :admins :total-count="stats?.admins_count" />
        </div>

        <!-- Right column: Members -->
        <div class="rounded-xl bg-card-elevated p-6 shadow-sm">
          <MembersSection :members :total-count="stats?.members_count" />
        </div>
      </div>

      <!-- Units by Type -->
      <div class="rounded-xl bg-card-elevated p-6 shadow-sm">
        <UnitsSection :unit-types="unitTypes" />
      </div>
    </Deferred>
  </div>
</template>
