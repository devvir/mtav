<script setup lang="ts">
import Card from '@/components/shared/Card.vue';
import { _ } from '@/composables/useTranslations';
import { ModalLink, useModal } from '@inertiaui/modal-vue';

defineProps<{
  member: Member;
}>();
</script>

<template>
  <Card class="h-full">
    <template v-slot:header>
      <ModalLink :href="route('members.show', member.id)" :class="{ 'pointer-events-none': useModal() }">
        <div class="flex justify-between">
          <img :src="member.avatar" alt="avatar" class="mr-base rounded-full ring ring-muted/25" />

          <div class="grid-rows-[2fr 1fr] grid text-right" :title="member.name">
            <div class="truncate text-xl">{{ member.name }}</div>

            <ModalLink
              @click.prevent.stop
              v-if="member.family.name"
              :href="route('families.show', member.family.id)"
              class="mt-1 text-xs text-accent/60 hocus:text-accent"
            >
              {{ `${_('Family')}: ${member.family.name}` }}
            </ModalLink>
          </div>
        </div>
      </ModalLink>
    </template>

    <ModalLink :href="route('members.show', member.id)">
      <div class="grid gap-3 text-sm opacity-60">
        <div class="truncate">{{ member.email }}</div>
        <div class="truncate">{{ member.phone }}</div>
      </div>
    </ModalLink>
  </Card>
</template>
