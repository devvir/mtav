<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import MaybeModal from '@/components/MaybeModal.vue';
import Card from '@/components/shared/Card.vue';
import { _ } from '@/composables/useTranslations';
import { ModalLink } from '@inertiaui/modal-vue';
import { Edit3Icon } from 'lucide-vue-next';
import ShowWrapper from '../shared/ShowWrapper.vue';
import Delete from './Crud/Delete.vue';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings

defineProps<{
  member: ApiResource<Member>;
}>();
</script>

<template>
  <Head :title="member.name" no-translation />

  <Breadcrumbs>
    <Breadcrumb route="members.index" text="Members" />
    <Breadcrumb route="members.show" :params="member.id">{{ member.name }}</Breadcrumb>
  </Breadcrumbs>

  <MaybeModal>
    <ShowWrapper>
      <Card class="size-full">
        <template v-slot:header>
          <div class="flex items-center-safe justify-start" :title="member.name">
            <img :src="member.avatar" alt="avatar" class="mr-wide w-24" />
            <div class="text-sm leading-wide text-muted-foreground">
              <div class="truncate text-2xl leading-12">{{ member.name }}</div>
              <div>{{ member.email }}</div>
              <div>{{ member.phone ?? 'N/A' }}</div>
            </div>
          </div>
        </template>

        <div class="mt-6 mb-3 flex flex-col justify-between gap-3 px-3 py-5">
          <div class="mb-6 space-y-4 text-sm">
            <div>TODO : project (iff current user is admin) with stats</div>
            <div>TODO : online status and last activity</div>
          </div>

          <ModalLink
            v-if="member.family.name"
            :href="route('families.show', member.family.id)"
            class="text-sm text-muted-foreground hover:text-gray-700 dark:hover:text-gray-300"
          >
            {{ _('Family') }}: {{ member.family.name }}
          </ModalLink>

          <div class="mt-4 text-sm text-muted-foreground">{{ _('Created') }}: {{ member.created_ago }}</div>
        </div>

        <ModalLink
          v-if="member.allows.update"
          class="flex items-center-safe justify-end gap-2 border-t pt-base"
          paddingClasses="p-8"
          :href="route('members.edit', member.id)"
        >
          {{ _('Edit Member') }} <Edit3Icon />
        </ModalLink>

        <Delete v-if="member.allows?.delete" :member class="mt-wide border-t border-foreground/10" />
      </Card>
    </ShowWrapper>
  </MaybeModal>
</template>
