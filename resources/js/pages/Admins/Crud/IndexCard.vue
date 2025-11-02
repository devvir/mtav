<script setup lang="ts">
import Card from '@/components/shared/Card.vue';
import CallToAction from '@/components/ui/button/CallToAction.vue';
import { currentUser } from '@/composables/useAuth';
import { _ } from '@/composables/useTranslations';
import { ModalLink, useModal } from '@inertiaui/modal-vue';
import { Edit3Icon } from 'lucide-vue-next';

const props = defineProps<{
  admin: ApiResource<Admin>;
}>();

const isCurrentUser = computed(() => currentUser.value?.id === props.admin.id);
</script>

<template>
  <Card>
    <ModalLink :href="route('admins.show', admin.id)" class="block space-y-4 focus:outline-0">
      <div class="grid grid-cols-[auto_1fr_auto] items-center gap-4">
        <img :src="admin.avatar" alt="avatar" class="size-14 shrink-0 rounded ring-2 ring-border" />

        <div class="space-y-1">
          <div class="truncate text-xl font-semibold text-text" :title="admin.name">{{ admin.name }}</div>
          <div class="truncate text-sm text-text-muted">{{ admin.email }}</div>
        </div>

        <ModalLink
          v-if="admin.allows.update && ! isCurrentUser"
          :href="route('admins.edit', admin.id)"
          @click.stop
          paddingClasses="p-8"
          class="min-h-[44px] @md:min-h-[36px] rounded-lg bg-surface-interactive p-3 ring-2 ring-border transition-all hover:bg-surface-interactive-hover hover:ring-border-strong focus:outline-0 focus:ring-2 focus:ring-focus-ring focus:ring-offset-2"
          :class="useModal() ? 'self-center' : 'self-start'"
        >
          <span :title="_('Edit Admin')"><Edit3Icon class="h-5 w-5" /></span>
        </ModalLink>
      </div>

      <CallToAction variant="default" :href="route('contact', admin.id)" class="w-full">
        {{ _('Contact') }}
      </CallToAction>
    </ModalLink>
  </Card>
</template>
