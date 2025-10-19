<script setup lang="ts">
import { SidebarGroup, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/layout/sidebar';
import { iAmAdmin } from '@/composables/useAuth';
import { projectIsSelected } from '@/composables/useProjects';
import { currentRoute } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import { Building2Icon, HomeIcon, LayoutGrid, LucideIcon, UsersIcon } from 'lucide-vue-next';

interface NavItem {
  label: string;
  route: string | ComputedRef<string>;
  icon: LucideIcon;
  onlyIf?: ComputedRef<boolean>;
  routes?: string[];
}

const allNavItems = reactive<NavItem[]>([
  {
    label: 'Dashboard',
    route: 'home',
    icon: HomeIcon,
    onlyIf: projectIsSelected,
    routes: ['home'],
  },
  {
    label: 'Gallery',
    route: 'gallery',
    icon: LayoutGrid,
    onlyIf: projectIsSelected,
    routes: ['gallery'],
  },
  {
    label: 'Members',
    route: computed(() => (usePage().props.state.groupMembers ? 'families.index' : 'users.index')),
    icon: UsersIcon,
    onlyIf: computed(() => projectIsSelected.value || iAmAdmin.value),
    routes: ['families.*', 'members.*', 'users.*'],
  },
  {
    label: 'Projects',
    route: 'projects.index',
    icon: Building2Icon,
    onlyIf: iAmAdmin,
    routes: ['projects.*'],
  },
]);

const navItems = computed(() => {
  return allNavItems.filter((item) => item.onlyIf?.value !== false);
});

const activeNavItem = computed(() =>
  navItems.value.find(
    ({ routes = [] }) => !!routes.map((r) => new RegExp(`^${r}$`)).find((re) => re.test(currentRoute.value)),
  ),
);
</script>

<template>
  <SidebarGroup class="space-y-base">
    <SidebarMenu>
      <SidebarMenuItem v-for="item in navItems" :key="item.label">
        <SidebarMenuButton as-child :is-active="item.label == activeNavItem?.label" :tooltip="item.label">
          <Link :href="route(item.route)" :class="{ 'pointer-events-none': item.route === currentRoute }" prefetch>
            <component :is="item.icon" />
            <span>{{ _(item.label) }}</span>
          </Link>
        </SidebarMenuButton>
      </SidebarMenuItem>
    </SidebarMenu>
  </SidebarGroup>
</template>
