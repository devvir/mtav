<script setup lang="ts">
import {
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import UserInfo from '@/components/UserInfo.vue';
import { _ } from '@/composables/useTranslations';
import { LogOut, Settings } from 'lucide-vue-next';

interface Props {
  user: User;
}

const handleLogout = () => {
  router.flushAll();
};

defineProps<Props>();
</script>

<template>
  <DropdownMenuLabel class="p-0 font-normal">
    <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
      <UserInfo :user="user" :show-email="true" />
    </div>
  </DropdownMenuLabel>
  <DropdownMenuSeparator />
  <DropdownMenuGroup>
    <DropdownMenuItem :as-child="true">
      <Link class="block w-full" :href="route('profile.edit')" prefetch as="button">
        <Settings class="mr-2 size-4" />
        {{ _('Settings') }}
      </Link>
    </DropdownMenuItem>
  </DropdownMenuGroup>
  <DropdownMenuSeparator />
  <DropdownMenuItem :as-child="true">
    <Link class="block w-full" method="post" :href="route('logout')" @click="handleLogout" as="button">
      <LogOut class="mr-2 size-4" />
      {{ _('Log out') }}
    </Link>
  </DropdownMenuItem>
</template>
