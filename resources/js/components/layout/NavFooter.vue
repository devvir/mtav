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
import { BookOpen, Folder, LucideIcon } from 'lucide-vue-next';

interface FooterItem {
  title: string;
  href: string;
  icon?: LucideIcon;
  if?: boolean;
}

defineProps<{
  class?: string;
}>();

const allFooterItems: FooterItem[] = [
  {
    title: _('Documentation'),
    href: 'https://laravel.com/docs/starter-kits#vue', // TODO : replace with actual documentation URL
    icon: BookOpen,
  },
  {
    title: _('Github Repo'),
    href: 'https://github.com/laravel/vue-starter-kit', // TODO : replace with actual repository URL
    icon: Folder,
    if: iAmAdmin.value,
  },
];

const footerItems = computed(() => allFooterItems.filter((item) => item.if !== false));
</script>

<template>
  <SidebarGroup :class="`group-data-[collapsible=icon]:p-0 ${$props.class || ''}`">
    <SidebarGroupContent>
      <SidebarMenu>
        <SidebarMenuItem v-for="item in footerItems" :key="item.title">
          <SidebarMenuButton class="hocus:text-accent-foreground/80" as-child>
            <a :href="item.href" target="_blank" rel="noopener noreferrer" class="px-2">
              <component :is="item.icon" />
              <span>{{ item.title }}</span>
            </a>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarGroupContent>
  </SidebarGroup>
</template>
