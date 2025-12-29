<script setup lang="ts">
import {
  SidebarGroup,
  SidebarGroupContent,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from '@/components/layout/sidebar';
import { iAmAdmin } from '@/composables/useAuth';
import { _ } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';
import { BookOpen, Code, Folder, LucideIcon } from 'lucide-vue-next';
import type { HTMLAttributes } from 'vue';

interface FooterItem {
  title: string;
  href: string;
  icon?: LucideIcon;
  if?: boolean;
  external?: boolean;
}

defineProps<{
  class?: HTMLAttributes['class'];
}>();

const allFooterItems: FooterItem[] = [
  {
    title: _('Documentation'),
    href: route('documentation.faq', { role: iAmAdmin.value ? 'admin' : 'member' }),
    icon: BookOpen,
    external: false,
  },
  {
    title: _('Github Repo'),
    href: 'https://github.com/devvir/mtav',
    icon: Folder,
    if: iAmAdmin.value,
    external: true,
  },
];

const isDev = computed(() => usePage().props.env === 'local');

if (isDev.value) {
  allFooterItems.unshift({
    title: _('Dev Dashboard'),
    href: route('dev.dashboard'),
    icon: Code,
    external: false,
  });
}

const footerItems = computed(() => allFooterItems.filter((item) => item.if !== false));
</script>

<template>
  <SidebarGroup :class="cn('group-data-[collapsible=icon]:p-0', $props.class)">
    <SidebarGroupContent>
      <SidebarMenu>
        <SidebarMenuItem v-for="item in footerItems" :key="item.title">
          <SidebarMenuButton class="hocus:text-accent-foreground" as-child>
            <a
              v-if="item.external"
              :href="item.href"
              target="_blank"
              rel="noopener noreferrer"
              class="px-2"
            >
              <component :is="item.icon" />
              <span>{{ item.title }}</span>
            </a>
            <Link v-else :href="item.href" class="px-2">
              <component :is="item.icon" />
              <span>{{ item.title }}</span>
            </Link>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarGroupContent>
  </SidebarGroup>
</template>
