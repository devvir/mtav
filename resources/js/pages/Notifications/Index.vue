<script setup lang="ts">
import DateTime from '@/components/feedback/DateTime.vue';
import EmptyState from '@/components/feedback/EmptyState.vue';
import Pagination from '@/components/feedback/Pagination.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';
import type { Notification, Paginated } from '@/types/models';
import { Link, router } from '@inertiajs/vue3';
import { BellOff, CheckCheck } from 'lucide-vue-next';

const props = defineProps<{
  notifications: Paginated<Notification>;
  filters: { q: string | null; unread: boolean };
}>();

const open = (notification: Notification) => {
  if (!notification.read)
    router.post(
      route('notifications.read', notification.id),
      {},
      { preserveScroll: true, preserveState: true },
    );
  if (notification.url) router.visit(notification.url);
};

const toggleUnread = () =>
  router.get(route('notifications.index'), props.filters.unread ? {} : { unread: 1 }, {
    preserveState: true,
  });
</script>

<template>
  <PageHeader :title="_('Notifications')">
    <template #actions>
      <Button variant="outline" :aria-pressed="filters.unread" @click="toggleUnread">
        {{ filters.unread ? _('Show all') : _('Only unread') }}
      </Button>
      <Button
        variant="outline"
        @click="router.post(route('notifications.readAll'), {}, { preserveScroll: true })"
      >
        <CheckCheck /> {{ _('Mark all as read') }}
      </Button>
    </template>
  </PageHeader>

  <ul
    v-if="notifications.data.length"
    class="divide-y divide-border-subtle overflow-hidden rounded-2xl border-2 border-border bg-surface"
  >
    <li v-for="notification in notifications.data" :key="notification.id">
      <button
        type="button"
        :class="
          cn(
            'flex w-full items-start gap-3 px-4 py-4 text-left transition hover:bg-surface-interactive-hover',
            !notification.read && 'bg-selection/25',
          )
        "
        @click="open(notification)"
      >
        <span
          :class="
            cn(
              'mt-2 size-2.5 shrink-0 rounded-full',
              notification.read ? 'bg-transparent' : 'bg-interactive',
            )
          "
          aria-hidden="true"
        />
        <span class="min-w-0 flex-1">
          <span class="block font-semibold">{{ notification.title }}</span>
          <span class="block text-text-muted">{{ notification.message }}</span>
          <DateTime
            :value="notification.created_at"
            relative
            class="mt-1 block text-sm text-text-subtle"
          />
        </span>
        <span class="sr-only">{{ notification.read ? _('Read') : _('Unread') }}</span>
      </button>
    </li>
  </ul>
  <EmptyState
    v-else
    :icon="BellOff"
    :title="filters.unread ? _('Nothing unread') : _('No notifications yet')"
  />

  <Pagination :page="notifications" />

  <p class="mt-6 text-sm text-text-muted">
    <Link :href="route('projects.index')" class="text-text-link hover:underline">{{
      _('Your projects')
    }}</Link>
  </p>
</template>
