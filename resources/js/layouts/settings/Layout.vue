<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { _ } from '@/composables/useTranslations';

interface SettingsNavItem {
  title: string;
  href: string;
  active: boolean;
}

const sidebarNavItems: SettingsNavItem[] = [
  {
    title: _('Profile'),
    href: route('profile.edit'),
  },
  {
    title: _('Password'),
    href: route('password.edit'),
  },
  {
    title: _('Appearance'),
    href: route('appearance'),
  },
].map((navItem) => ({ ...navItem, active: location.href === navItem.href }));
</script>

<template>
  <div class="px-4 py-6">
    <!-- Constrained container for all settings content -->
    <div class="mx-auto max-w-4xl">
      <!-- Navigation Tabs -->
      <nav
        class="mb-8 flex flex-row space-x-1 overflow-x-auto border-b border-border p-1 pb-4 xs:space-x-2"
      >
        <Button
          v-for="item in sidebarNavItems"
          :key="item.href"
          variant="ghost"
          size="sm"
          :class="[
            'text-xs whitespace-nowrap xs:text-sm',
            item.active
              ? 'cursor-default bg-surface-interactive font-medium text-text'
              : 'text-text-muted hover:bg-surface-interactive/50 hover:text-text',
          ]"
          :as-child="item.active"
        >
          <span v-if="item.active">
            {{ item.title }}
          </span>
          <Link v-else :href="item.href">
            {{ item.title }}
          </Link>
        </Button>
      </nav>

      <!-- Content Area -->
      <div class="w-full">
        <slot />
      </div>
    </div>
  </div>
</template>
