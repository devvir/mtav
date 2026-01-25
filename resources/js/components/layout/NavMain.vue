<script setup lang="ts">
import {
  SidebarGroup,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  useSidebar,
} from '@/components/layout/sidebar';
import { can } from '@/composables/useAuth';
import { currentRoute } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import { projectIsSelected } from '@/state/useCurrentProject';
import { useMemberGrouping } from '@/state/useMemberGrouping';
import {
  Building2Icon,
  CalendarIcon,
  FilesIcon,
  HomeIcon,
  LayoutGrid,
  LucideIcon,
  TrophyIcon,
  UsersIcon,
} from 'lucide-vue-next';

interface NavItem {
  label: MaybeRef<string>;
  route: MaybeRef<string>;
  icon: LucideIcon;
  onlyIf?: MaybeRef<boolean>;
  routes?: string[];
}

const { groupMembers } = useMemberGrouping();
const { setOpenMobile } = useSidebar();

const allNavItems: NavItem[] = [
  {
    label: 'Dashboard',
    route: 'dashboard',
    icon: HomeIcon,
    onlyIf: projectIsSelected,
    routes: ['dashboard'],
  },
  {
    label: 'Lottery',
    route: 'lottery',
    icon: TrophyIcon,
    onlyIf: projectIsSelected,
    routes: ['lottery.*'],
  },
  {
    label: computed(() => (groupMembers.value ? 'Families' : 'Members')),
    route: computed(() => (groupMembers.value ? 'families.index' : 'members.index')),
    icon: UsersIcon,
    onlyIf: can.viewAny('members'),
    routes: ['families.*', 'members.*'],
  },
  {
    label: 'Gallery',
    route: 'media.index',
    icon: LayoutGrid,
    onlyIf: projectIsSelected,
    routes: ['media.*'],
  },
  {
    label: 'Events',
    route: 'events.index',
    icon: CalendarIcon,
    onlyIf: projectIsSelected,
    routes: ['events.*'],
  },
  {
    label: 'Documents',
    route: 'documents.index',
    icon: FilesIcon,
    onlyIf: projectIsSelected,
    routes: ['documents.*'],
  },
  {
    label: 'Projects',
    route: 'projects.index',
    icon: Building2Icon,
    onlyIf: can.viewAny('projects'),
    routes: ['projects.*'],
  },
];

const navItems = computed(() => allNavItems.filter((item) => toValue(item.onlyIf) !== false));

const activeNavItem = computed(() =>
  navItems.value.find(
    ({ routes = [] }) =>
      !!routes.map((r) => new RegExp(`^${r}$`)).find((re) => re.test(currentRoute.value)),
  ),
);
</script>

<template>
  <SidebarGroup class="space-y-base">
    <SidebarMenu>
      <SidebarMenuItem v-for="item in navItems" :key="item.label">
        <SidebarMenuButton
          as-child
          :is-active="item.label == activeNavItem?.label"
          :tooltip="_(toValue(item.label))"
        >
          <Link
            :href="route(toValue(item.route))"
            :class="{ 'pointer-events-none': item.route === currentRoute }"
            prefetch
            @click="setOpenMobile(false)"
          >
            <component :is="item.icon" />
            <span>{{ _(toValue(item.label)) }}</span>
          </Link>
        </SidebarMenuButton>
      </SidebarMenuItem>
    </SidebarMenu>
  </SidebarGroup>
</template>
