<script setup lang="ts">
import Card from '@/components/shared/Card.vue';
import { _ } from '@/composables/useTranslations';
import { ModalLink } from '@inertiaui/modal-vue';

defineProps<{
  user: User;
}>();
</script>

<template>
  <Card class="h-full">
    <template v-slot:header>
      <ModalLink :href="route('users.show', user.id)">
        <div class="flex justify-between">
          <img :src="user.avatar" alt="avatar" class="mr-base rounded-full ring ring-muted/25" />

          <div class="grid-rows-[2fr 1fr] grid text-right" :title="user.name">
            <div class="truncate text-xl">{{ user.name }}</div>

            <ModalLink
              @click.prevent.stop
              v-if="user.family.name"
              :href="route('families.show', user.family.id)"
              class="mt-1 text-xs text-accent/60 hocus:text-accent"
            >
              {{ `${_('Family')}: ${user.family.name}` }}
            </ModalLink>
          </div>
        </div>
      </ModalLink>
    </template>

    <ModalLink :href="route('users.show', user.id)">
      <div class="grid gap-3 text-sm opacity-60">
        <div class="truncate">{{ user.email }}</div>
        <div class="truncate">{{ user.phone }}</div>
      </div>
    </ModalLink>
  </Card>
</template>
