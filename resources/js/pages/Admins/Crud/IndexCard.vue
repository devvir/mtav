<script setup lang="ts">
import Avatar from '@/components/Avatar.vue';
import Card from '@/components/shared/Card.vue';
import EditButton from '@/components/EditButton.vue';
import CallToAction from '@/components/ui/button/CallToAction.vue';
import { currentUser } from '@/composables/useAuth';
import { _ } from '@/composables/useTranslations';
import { ModalLink, useModal } from '@inertiaui/modal-vue';

const props = defineProps<{
  admin: ApiResource<Admin>;
}>();

const isCurrentUser = computed(() => currentUser.value?.id === props.admin.id);
</script>

<template>
  <Card>
    <ModalLink
      :href="route('admins.show', admin.id)"
      class="block space-y-4 focus:outline-0"
      :class="{ 'pointer-events-none': useModal() }"
    >
      <div class="grid grid-cols-[auto_1fr_auto] items-center gap-4">
        <Avatar :subject="admin" size="lg" class="ring-2 ring-border" />

        <div class="space-y-1">
          <div class="truncate text-xl font-semibold text-text" :title="admin.name">{{ admin.name }}</div>
          <div class="truncate text-sm text-text-muted">{{ admin.email }}</div>
        </div>

        <EditButton v-if="!isCurrentUser" :resource="admin" route-name="admins.edit" />
      </div>

      <CallToAction variant="default" :href="route('contact', admin.id)" class="w-full">
        {{ _('Contact') }}
      </CallToAction>
    </ModalLink>
  </Card>
</template>
