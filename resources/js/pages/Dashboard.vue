<script setup lang="ts">
// Copilot - pending review
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import { router } from '@inertiajs/vue3';
import AdminsSection from './Dashboard/admins/AdminsSection.vue';
import EventsSection from './Dashboard/events/EventsSection.vue';
import FamiliesSection from './Dashboard/families/FamiliesSection.vue';
import GallerySection from './Dashboard/gallery/GallerySection.vue';
import MembersSection from './Dashboard/members/MembersSection.vue';
import OverviewSection from './Dashboard/overview/OverviewSection.vue';
import SkeletonCard from './Dashboard/shared/SkeletonCard.vue';
import UnitsSection from './Dashboard/units/UnitsSection.vue';

const props = defineProps<{
  project: Project;
  families?: Family[];
  members?: Member[];
  unitTypes?: UnitType[];
  admins?: Admin[];
  events?: Event[];
}>();

// Load lazy data on mount
onMounted(() => {
  // Only reload if we don't have the data yet
  if (!props.families || !props.members || !props.unitTypes || !props.admins || !props.events) {
    router.reload({
      only: ['families', 'members', 'unitTypes', 'admins', 'events'],
    });
  }
});

// Calculate how many families to show based on number of admins
// Goal: families + admins height ≈ members height
// Account for section headers (families has 1 header, admins has 1 header, members has 1 header)
// We need to subtract 1 row worth of families to account for the extra admin header
const familiesToShow = computed(() => {
  if (!props.families || !props.admins || !props.members) return 0;

  const adminRows = Math.ceil(props.admins.length / 2); // admins in 2 columns
  const memberRows = Math.ceil(props.members.length / 2); // members in 2 columns

  // Subtract 1 row for the extra header in the left column (families + admins vs just members)
  const availableRowsForFamilies = Math.max(0, memberRows - adminRows - 1);
  const maxFamiliesToShow = availableRowsForFamilies * 2; // 2 columns

  return Math.min(props.families.length, maxFamiliesToShow);
});

const visibleFamilies = computed(() => props.families?.slice(0, familiesToShow.value) || []);

const stats = computed(() => ({
  admins: props.project.admins_count,
  members: props.project.members_count,
  families: props.project.families_count,
  unit_types: props.project.unit_types_count,
  units: props.project.units_count,
  media: props.project.media_count,
  events: props.project.events_count,
}));
</script>

<template>
  <Head title="Project Dashboard" />

  <Breadcrumbs>
    <Breadcrumb route="dashboard" text="Project Dashboard" />
  </Breadcrumbs>

  <div class="flex h-full flex-1 flex-col gap-8 p-4">
    <!-- Overview Stats -->
    <OverviewSection :stats />

    <!-- Gallery and Events side by side -->
    <div class="grid gap-6 @4xl:grid-cols-2">
      <div class="rounded-xl bg-card-elevated p-6 shadow-sm">
        <GallerySection />
      </div>
      <div class="rounded-xl bg-card-elevated p-6 shadow-sm">
        <EventsSection :events />
      </div>
    </div>

    <!-- Families/Admins and Members side by side -->
    <div class="grid gap-6 @4xl:grid-cols-2">
      <!-- Left column: Families + Admins -->
      <div class="flex flex-col gap-6 rounded-xl bg-card-elevated p-6 shadow-sm">
        <template v-if="families && admins">
          <FamiliesSection :families="visibleFamilies" :total-count="stats.families" />
          <AdminsSection :admins />
        </template>
        <template v-else>
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
        </template>
      </div>

      <!-- Right column: Members -->
      <div class="rounded-xl bg-card-elevated p-6 shadow-sm">
        <template v-if="members">
          <MembersSection :members :total-count="stats.members" />
        </template>
        <template v-else>
          <div class="space-y-3">
            <div class="h-6 w-32 animate-pulse rounded bg-surface-sunken" />
            <div class="grid gap-4 @md:grid-cols-2">
              <SkeletonCard v-for="i in 10" :key="i" />
            </div>
          </div>
        </template>
      </div>
    </div>

    <!-- Units by Type -->
    <div class="rounded-xl bg-card-elevated p-6 shadow-sm">
      <template v-if="unitTypes">
        <UnitsSection :unit-types="unitTypes" />
      </template>
      <template v-else>
        <div class="space-y-4">
          <div class="h-6 w-32 animate-pulse rounded bg-surface-sunken" />
          <div class="grid gap-4 @2xl:grid-cols-2">
            <SkeletonCard v-for="i in 4" :key="i" height="h-40" />
          </div>
        </div>
      </template>
    </div>
  </div>
</template>
