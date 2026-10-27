<script setup lang="ts">
import AppShell from '@/components/layout/AppShell.vue';
import type { NavItem } from '@/components/layout/types';
import { route, routeIs } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import { Bell, FolderKanban, KeyRound, Palette, UserRound } from 'lucide-vue-next';

/**
 * Pages about the person rather than a project: their projects, notifications, settings.
 */
const nav = computed<NavItem[]>(() => [
  {
    label: _('Your projects'),
    href: route('projects.index'),
    icon: FolderKanban,
    active: routeIs('projects.index', 'projects.create'),
  },
  {
    label: _('Notifications'),
    href: route('notifications.index'),
    icon: Bell,
    active: routeIs('notifications.*'),
  },
  {
    label: _('Profile'),
    href: route('profile.edit'),
    icon: UserRound,
    active: routeIs('profile.*'),
  },
  {
    label: _('Password'),
    href: route('password.edit'),
    icon: KeyRound,
    active: routeIs('password.edit'),
  },
  {
    label: _('Appearance'),
    href: route('appearance'),
    icon: Palette,
    active: routeIs('appearance'),
  },
]);
</script>

<template>
  <AppShell :heading="_('Your account')" :nav>
    <slot />
  </AppShell>
</template>
