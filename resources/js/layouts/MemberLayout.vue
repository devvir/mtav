<script setup lang="ts">
import AppShell from '@/components/layout/AppShell.vue';
import type { NavItem } from '@/components/layout/types';
import { route, routeIs } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import { usePage } from '@inertiajs/vue3';
import {
  CalendarDays,
  Dices,
  House,
  Image,
  LayoutGrid,
  Map,
  Settings2,
  UserPlus,
  Users,
  UsersRound,
} from 'lucide-vue-next';

/**
 * The member app: what people do in a project.
 */
const page = usePage();
const project = computed(() => page.props.project!);

const nav = computed<NavItem[]>(() => [
  { label: _('Home'), href: route('projects.show'), icon: House, active: routeIs('projects.show') },
  {
    label: _('Events'),
    href: route('events.index'),
    icon: CalendarDays,
    active: routeIs('events.*'),
  },
  { label: _('Files'), href: route('media.index'), icon: Image, active: routeIs('media.*') },
  { label: _('Lottery'), href: route('lottery.show'), icon: Dices, active: routeIs('lottery.*') },
  {
    label: _('Families'),
    href: route('families.index'),
    icon: UsersRound,
    active: routeIs('families.*'),
  },
  { label: _('People'), href: route('people.index'), icon: Users, active: routeIs('people.*') },
  { label: _('Units'), href: route('units.index'), icon: LayoutGrid, active: routeIs('units.*') },
  { label: _('Plan'), href: route('plan.show'), icon: Map, active: routeIs('plan.show') },
  ...(project.value.family
    ? [
        {
          label: _('Invite to my family'),
          href: route('invitations.index'),
          icon: UserPlus,
          active: routeIs('invitations.*'),
        },
      ]
    : []),
]);

const tabs = computed(() => nav.value.slice(0, 4));

const switchTo = computed(() =>
  project.value.can.manage
    ? { label: _('Manage project'), href: route('manage.overview'), icon: Settings2 }
    : null,
);
</script>

<template>
  <AppShell :heading="project.name" :nav :tabs :switch-to="switchTo">
    <slot />
  </AppShell>
</template>
