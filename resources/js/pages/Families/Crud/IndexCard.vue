<script setup lang="ts">
import Card from '@/components/shared/Card.vue';
import { _ } from '@/composables/useTranslations';
import { ModalLink, useModal } from '@inertiaui/modal-vue';

defineProps<{
  family: Family;
}>();
</script>

<template>
  <Card class="h-full">
    <template v-slot:header>
      <ModalLink
        :href="route('families.show', family.id)"
        :title="family.name"
        class="block focus:outline-0"
        :class="{ 'pointer-events-none': useModal() }"
      >
        <div class="flex items-center justify-between gap-3">
          <img :src="family.avatar" alt="avatar" class="size-10 shrink-0 rounded ring-2 ring-border" />

          <div class="flex-1 truncate text-right pl-base">
            <div class="text-xs font-medium uppercase tracking-wide text-text-subtle pb-1">{{ _('Family') }}</div>
            <div class="truncate text-xl font-semibold text-text">{{ family.name }}</div>
          </div>
        </div>

        <slot name="header" />
      </ModalLink>
    </template>

    <div class="my-4 flex flex-col justify-between gap-2">
      <slot name="content-before" />

      <ModalLink
        v-for="member in family.members"
        :key="member.id"
        class="group min-h-[44px] @md:min-h-[36px] cursor-pointer rounded-lg p-2 text-text transition-all hover:bg-surface-interactive-hover focus:outline-none focus:ring-2 focus:ring-focus-ring focus:ring-offset-2 focus:ring-offset-focus-ring-offset"
        :href="route('members.show', member.id)"
        :title="member.name"
        prefetch="click"
      >
        <div class="flex items-center justify-start gap-3">
          <img :src="member.avatar" alt="avatar" class="size-8 shrink-0 rounded-full ring-2 ring-border" />
          <div class="truncate text-sm font-medium group-hover:text-text-link" :title="member.name">
            {{ member.name }}
          </div>
        </div>
      </ModalLink>

      <section v-if="!family.members?.length" class="flex size-full items-center justify-center py-6 text-sm text-text-muted">
        {{ _('No Members yet') }}
      </section>

      <slot name="content-after" />
    </div>
  </Card>
</template>
