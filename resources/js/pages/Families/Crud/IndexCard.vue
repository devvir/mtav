<script setup lang="ts">
import Card from '@/components/shared/Card.vue';
import { _ } from '@/composables/useTranslations';
import { ModalLink, useModal } from '@inertiaui/modal-vue';
import { Edit3Icon } from 'lucide-vue-next';

defineProps<{
  family: Family;
}>();
</script>

<template>
  <Card class="h-full">
    <template v-slot:header>
      <div class="flex items-start justify-between gap-3">
        <ModalLink
          :href="route('families.show', family.id)"
          :title="family.name"
          class="flex min-w-0 flex-1 items-center gap-3 focus:outline-0"
          :class="{ 'pointer-events-none': useModal() }"
        >
          <img :src="family.avatar" alt="avatar" class="size-10 shrink-0 rounded ring-2 ring-border" />

          <div class="min-w-0 flex-1 truncate pl-base">
            <div class="pb-1 text-xs font-medium uppercase tracking-wide text-text-subtle">{{ _('Family') }}</div>
            <div class="truncate text-xl font-semibold text-text">{{ family.name }}</div>
          </div>
        </ModalLink>

        <ModalLink
          v-if="family.allows?.update"
          :href="route('families.edit', family.id)"
          paddingClasses="p-8"
          class="shrink-0 rounded-lg bg-surface-interactive p-3 ring-2 ring-border transition-all hover:bg-surface-interactive-hover hover:ring-border-strong focus:outline-0 focus:ring-2 focus:ring-focus-ring focus:ring-offset-2"
          :title="_('Edit Family')"
        >
          <Edit3Icon class="h-5 w-5" />
        </ModalLink>
      </div>

      <slot name="header" />
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
