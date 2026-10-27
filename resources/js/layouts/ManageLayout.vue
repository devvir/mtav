<script setup lang="ts">
import AppShell from '@/components/layout/AppShell.vue';
import type { NavItem } from '@/components/layout/types';
import { route, routeIs } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import { Link, usePage } from '@inertiajs/vue3';
import {
  ArrowLeft,
  CalendarDays,
  Dices,
  Gauge,
  Layers,
  LayoutGrid,
  Mail,
  Map,
  Settings,
  TriangleAlert,
  Users,
  UsersRound,
} from 'lucide-vue-next';

/**
 * The project admin area: configuring a project.
 */
const page = usePage();
const project = computed(() => page.props.project!);

const nav = computed<NavItem[]>(() => [
  {
    label: _('Overview'),
    href: route('manage.overview'),
    icon: Gauge,
    active: routeIs('manage.overview'),
  },
  {
    label: _('Unit types'),
    href: route('manage.unit_types.index'),
    icon: Layers,
    active: routeIs('manage.unit_types.*'),
  },
  {
    label: _('Units'),
    href: route('manage.units.index'),
    icon: LayoutGrid,
    active: routeIs('manage.units.*'),
  },
  {
    label: _('Plan'),
    href: route('manage.plan.edit'),
    icon: Map,
    active: routeIs('manage.plan.*'),
  },
  {
    label: _('Families'),
    href: route('manage.families.index'),
    icon: UsersRound,
    active: routeIs('manage.families.*'),
  },
  {
    label: _('People'),
    href: route('manage.people.index'),
    icon: Users,
    active: routeIs('manage.people.*'),
  },
  {
    label: _('Invitations'),
    href: route('manage.invitations.index'),
    icon: Mail,
    active: routeIs('manage.invitations.*'),
  },
  {
    label: _('Events'),
    href: route('manage.events.index'),
    icon: CalendarDays,
    active: routeIs('manage.events.*'),
  },
  {
    label: _('Lottery'),
    href: route('manage.lottery.show'),
    icon: Dices,
    active: routeIs('manage.lottery.*'),
  },
  {
    label: _('Settings'),
    href: route('manage.settings.edit'),
    icon: Settings,
    active: routeIs('manage.settings.*'),
  },
]);

const switchTo = computed(() => ({
  label: _('Back to the project'),
  href: route('projects.show'),
  icon: ArrowLeft,
}));
</script>

<template>
  <AppShell
    :heading="_('Managing {project}', { project: project.name })"
    :nav
    :switch-to="switchTo"
  >
    <template v-if="!project.is_approved" #banner>
      <div
        class="flex items-center gap-3 border-b-2 border-warning/40 bg-warning-subtle px-4 py-2 text-sm text-warning-subtle-foreground sm:px-6"
      >
        <TriangleAlert class="size-5 shrink-0" />
        <p class="flex-1">
          {{
            project.approval_requested_at
              ? _('Approval requested: limits apply until a superadmin approves the project.')
              : _('This project is awaiting approval: some limits apply.')
          }}
        </p>
        <Link :href="route('manage.overview')" class="font-semibold underline">{{
          _('Details')
        }}</Link>
      </div>
    </template>
    <slot />
  </AppShell>
</template>
