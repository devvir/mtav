<script setup lang="ts">
import Card from '@/components/shared/Card.vue';
import CallToAction from '@/components/ui/button/CallToAction.vue';
import { _ } from '@/composables/useTranslations';
import { ModalLink, useModal } from '@inertiaui/modal-vue';
import { Edit3Icon } from 'lucide-vue-next';

defineProps<{
  admin: ApiResource<Admin>;
}>();
</script>

<template>
  <Card>
    <ModalLink :href="route('admins.show', admin.id)" class="block space-y-2 focus:outline-0">
      <div class="grid grid-cols-[auto_1fr_auto] items-stretch gap-4">
        <img :src="admin.avatar" alt="avatar" class="size-14 shrink-0 rounded" />

        <div class="grid grid-rows-[auto_auto_1fr] gap-3 text-xl" :title="admin.name">
          <div class="truncate">{{ admin.name }}</div>
          <div class="truncate text-xs">{{ admin.email }}</div>
        </div>

        <ModalLink
          v-if="admin.allows.update"
          :href="route('admins.edit', admin.id)"
          @click.stop
          paddingClasses="p-8"
          class="rounded-full bg-accent-foreground/8 p-3 align-bottom ring ring-foreground/40 hocus:bg-accent-foreground/20 hocus:ring-foreground"
          :class="useModal() ? 'mr-8 mb-2 self-center' : 'self-start'"
        >
          <span :title="_('Edit Admin')"><Edit3Icon class="size-5" /></span>
        </ModalLink>
      </div>

      <CallToAction variant="default" :href="route('admins.contact', admin.id)" class="w-full">
        {{ _('Contact') }}
      </CallToAction>
    </ModalLink>
  </Card>
</template>
