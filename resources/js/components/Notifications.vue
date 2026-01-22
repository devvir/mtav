<script setup lang="ts">
import { Dropdown, DropdownContent, DropdownTrigger } from '@/components/dropdown';
import { notifications } from '@/composables/useAuth';
import { _ } from '@/composables/useTranslations';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import { Bell } from 'lucide-vue-next';
import { route as routeUri } from 'ziggy-js';

const markAsRead = (notification: Notification, close: () => void) => {
  if (!notification.is_read) axios.post(routeUri('notifications.read', notification.id));

  notification.is_read = true;
  setTimeout(() => close(), 100);
};

const markAllAsRead = () => {
  axios.post(routeUri('notifications.readAll'));

  // Optimistically mark all as read
  notifications.value.recent.forEach((n: Notification) => (n.is_read = true));
};
</script>

<template>
  <Dropdown v-slot="{ close }">
    <DropdownTrigger
      class="group relative -mr-2 flex min-h-11 cursor-pointer items-center rounded-xs px-2 text-text-subtle outline-offset-8 transition-colors @md:min-h-9 hocus:text-text"
      :aria-label="_('Notifications')"
    >
      <Bell class="size-7" />

      <!-- Unread count badge -->
      <span
        v-if="notifications.unread"
        class="absolute -top-0.5 -right-0.5 flex size-5 items-center justify-center rounded-full bg-accent-foreground text-xs font-bold text-background"
      >
        {{ notifications.unread > 9 ? '9+' : notifications.unread }}
      </span>
    </DropdownTrigger>

    <DropdownContent
      class="top-10 right-0 mr-3 w-80 origin-top overflow-hidden rounded-xl border border-border bg-surface-elevated shadow shadow-accent/70 backdrop-blur-2xl"
    >
      <!-- Header -->
      <div class="flex items-center justify-between border-b border-border/30 px-4 py-3">
        <h3 class="text-sm font-semibold text-text">{{ _('Notifications') }}</h3>
        <button
          v-if="notifications.unread"
          class="text-xs text-text-subtle hocus:text-text"
          @click="markAllAsRead"
        >
          {{ _('Mark all as read') }}
        </button>
      </div>

      <!-- Notifications list -->
      <div class="max-h-96 divide-y divide-border/70 overflow-y-auto">
        <component
          :is="notification.data?.action ? Link : 'div'"
          v-for="notification in notifications.recent"
          :key="notification.id"
          :href="notification.data?.action"
          class="flex items-start gap-3 px-4 py-3 transition-colors"
          :class="[
            notification.is_read
              ? 'bg-transparent opacity-70'
              : 'bg-accent-foreground/10 font-medium',
            notification.data?.action ? 'cursor-pointer hocus:bg-accent-foreground/5' : '',
          ]"
          @click.capture="markAsRead(notification, close)"
        >
          <div class="flex flex-1 flex-col">
            <p class="mb-0.5 text-xs text-text-subtle/70">
              {{ notification.data?.title }}
            </p>
            <p class="text-sm text-text" :class="notification.is_read ? '' : 'font-medium'">
              {{ notification.data?.message }}
            </p>
            <p class="mt-1 text-right text-xs text-text-subtle/60" :title="notification.created_at">
              {{ notification.created_ago }}
            </p>
          </div>
        </component>

        <!-- Empty state -->
        <div
          v-if="notifications.recent.length === 0"
          class="flex flex-col items-center justify-center py-12 text-text-subtle"
        >
          <Bell class="mb-3 size-12 opacity-30" />
          <p class="text-sm">{{ _('No notifications yet') }}</p>
        </div>
      </div>

      <!-- See all link -->
      <div class="border-t border-border/30 px-4 py-3">
        <Link
          :href="routeUri('notifications.index')"
          class="block text-center text-sm text-text-subtle hocus:text-text"
          @click="close()"
          >{{ _('See all') }}</Link
        >
      </div>
    </DropdownContent>
  </Dropdown>
</template>
