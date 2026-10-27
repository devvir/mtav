<script setup lang="ts">
import AppShell from '@/components/layout/AppShell.vue';
import type { NavItem } from '@/components/layout/types';
import { route, routeIs } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import { FolderKanban, UserRound } from 'lucide-vue-next';

/**
 * The platform console: superadmins only.
 */
const nav = computed<NavItem[]>(() => [
  {
    label: _('Projects'),
    href: route('admin.projects.index'),
    icon: FolderKanban,
    active: routeIs('admin.projects.*'),
  },
  {
    label: _('Accounts'),
    href: route('admin.users.index'),
    icon: UserRound,
    active: routeIs('admin.users.*'),
  },
]);
</script>

<template>
  <AppShell :heading="_('Platform console')" :nav>
    <slot />
  </AppShell>
</template>
