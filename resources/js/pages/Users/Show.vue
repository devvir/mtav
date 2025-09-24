<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import MaybeModal from '@/components/MaybeModal.vue';
import Card from '@/components/shared/Card.vue';
import { _ } from '@/composables/useTranslations';
import Delete from '@/pages/Users/Crud/Delete.vue';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings

defineProps<{
  user: User;
}>();
</script>

<template>
  <Head :title="user.name" no-translation />

  <Breadcrumbs>
    <Breadcrumb route="users.index" text="Members" />
    <Breadcrumb route="users.show" :params="user.id">{{ user.name }}</Breadcrumb>
  </Breadcrumbs>

  <MaybeModal>
    <div class="flex h-full justify-center">
      <Card class="size-full">
        <template v-slot:header>
          <div class="flex items-center-safe justify-start" :title="user.name">
            <img :src="user.avatar" alt="avatar" class="mr-wide w-24" />
            <div class="text-sm leading-wide text-muted-foreground">
              <div class="truncate text-2xl leading-12">{{ user.name }}</div>
              <div>{{ user.email }}</div>
              <div>{{ user.phone ?? 'N/A' }}</div>
            </div>
          </div>
        </template>

        <div class="mt-6 mb-3 flex flex-col justify-between gap-3 px-3 py-5">
          <div class="mb-6 space-y-4 text-sm">
            <div>TODO</div>
            <div>family basics</div>
            <div>project (iff no project selected) with stats</div>
            <div>online status and last activity</div>
          </div>

          <Link
            v-if="user.family.name"
            :href="route('families.show', user.family.id)"
            class="text-sm text-muted-foreground hover:text-gray-700 dark:hover:text-gray-300"
          >
            {{ _('Family') }}: {{ user.family.name }}
          </Link>

          <div class="mt-4 text-sm text-muted-foreground">{{ _('Created') }}: {{ user.created_ago }}</div>
        </div>

        <Delete v-if="user.allows?.delete" :user="user" class="mt-wide border-t border-foreground/10" />
      </Card>
    </div>
  </MaybeModal>
</template>
