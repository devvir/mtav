<script setup lang="ts">
import { route } from 'ziggy-js';
import axios from 'axios';
import { Check, CheckCheck, Globe, Lock, Briefcase } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Card, CardHeader } from '@/components/card';
import { _ } from '@/composables/useTranslations';
import { fromUTC } from '@/composables/useDates';
import { currentUser, iAmSuperadmin } from '@/composables/useAuth';

const props = defineProps<{
  notification: Notification;
}>();

const isRead = ref(props.notification.is_read);

const showProjectName = computed(() => {
  if (props.notification.target !== 'project') return false;
  if (iAmSuperadmin.value) return true;
  return (currentUser.value?.projects?.length ?? 0) > 1;
});

const projectName = computed(() => {
  if (!showProjectName.value || !props.notification.target_id) return undefined;
  return currentUser.value?.projects?.find((p: { id: number }) => p.id === props.notification.target_id)?.name;
});

const targetInfo = computed(() => {
  switch (props.notification.target) {
    case 'global':
      return { icon: Globe, label: _('Global'), variant: 'secondary' as const };
    case 'project':
      return { icon: Briefcase, label: _('Project'), variant: 'outline' as const };
    default:
      return { icon: Lock, label: _('Private'), variant: 'default' as const };
  }
});

const markAsRead = () => {
  if (!isRead.value) axios.post(route('notifications.read', props.notification.id));
  isRead.value = true;
};

const markAsUnread = () => {
  if (isRead.value) axios.post(route('notifications.unread', props.notification.id));
  isRead.value = false;
};
</script>

<template>
  <Card
    :resource="notification"
    :dimmed="isRead"
    :class="!isRead ? 'border-l-4 border-l-primary' : ''"
    :card-link="notification.data?.action" no-modal
    @click="markAsRead"
  >
    <CardHeader :title="notification.data.message">
      <template v-slot:kicker>
        <div class="flex gap-2 flex-wrap mb-2 @max-sm:flex-col">
          <div class="flex items-center gap-2">
            <component :is="targetInfo.icon" class="size-3 inline-block" />
            {{ targetInfo.label }}
            <p v-if="projectName">{{ projectName }}</p>
            <span>|</span>
          </div>
          {{ notification.data.title }}
          <span class="@max-xs:hidden" :title="fromUTC(notification.created_at)">
            {{ notification.created_ago }}
          </span>
        </div>
      </template>

      <template v-slot:actions>
        <Button
          variant="ghost"
          size="icon"
          class="size-8"
          @click.prevent.stop="isRead ? markAsUnread() : markAsRead()"
          :title="isRead ? _('Mark as unread') : _('Mark as read')"
        >
          <CheckCheck v-if="isRead" class="size-4 text-muted-foreground" />
          <Check v-else class="size-4" />
        </Button>
      </template>
    </CardHeader>
  </Card>
</template>
