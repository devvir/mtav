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
        class="text-right"
        :class="{ 'pointer-events-none': useModal() }"
      >
        <div class="flex items-center justify-end">
          <div class="mr-2 text-sm text-muted-foreground/50">{{ _('Family') }}</div>
          <div class="truncate text-xl">{{ family.name }}</div>
        </div>

        <slot name="header" />
      </ModalLink>
    </template>

    <div class="my-base-y flex flex-col justify-between gap-1 lg:gap-1.5">
      <slot name="content-before" />

      <ModalLink
        v-for="member in family.members"
        :key="member.id"
        class="cursor-pointer rounded-2xl p-1 text-[#1b1b18] hover:bg-accent/40 dark:text-[#EDEDEC]"
        :href="route('users.show', member.id)"
        :title="member.name"
        prefetch="click"
      >
        <div class="flex items-center-safe justify-start gap-3">
          <!-- TODO: extract component -->
          <img :src="member.avatar" alt="avatar" width="40px" class="rounded-full ring ring-muted/25" />
          <div class="max-w-32 truncate text-sm" :title="member.name">
            {{ member.name }}
          </div>
        </div>
      </ModalLink>

      <section v-if="!family.members?.length" class="flex size-full items-center justify-center">
        {{ _('No Members yet') }}
      </section>

      <slot name="content-after" />
    </div>
  </Card>
</template>
