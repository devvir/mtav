<script setup lang="ts">
import Avatar from '@/components/Avatar.vue';
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
      <ModalLink
        :href="route('members.show', member.id)"
        class="block focus:outline-0"
        :class="{ 'pointer-events-none': useModal() }"
      >
        <div class="flex items-center justify-between gap-3">
          <Avatar :subject="member" size="lg" class="rounded-full ring-2 ring-border" />

          <div class="flex-1 text-right truncate pl-base" :title="member.name">
            <div class="truncate text-xl font-semibold text-text">{{ member.name }}</div>

            <ModalLink
              @click.prevent.stop
              v-if="'name' in member.family"
              :href="route('families.show', member.family.id)"
              class="mt-1 inline-block min-h-[44px] @md:min-h-[36px] items-center text-xs font-medium text-text-link hover:text-text-link-hover focus:outline-none focus:ring-2 focus:ring-focus-ring focus:ring-offset-1"
            >
              {{ `${_('Family')}: ${member.family.name}` }}
            </ModalLink>
          </div>
        </div>
      </ModalLink>
    </template>

    <ModalLink
      :href="route('members.show', member.id)"
      class="block py-2 focus:outline-none focus:ring-2 focus:ring-focus-ring focus:ring-offset-2"
    >
      <div class="space-y-2 text-sm text-text-muted">
        <div class="truncate">{{ member.email }}</div>
        <div class="truncate">{{ member.phone }}</div>
      </div>
    </ModalLink>
  </Card>
</template>
