<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import InfinitePaginator from '@/components/pagination/InfinitePaginator.vue';
import { ModalLink } from '@inertiaui/modal-vue';

defineProps<{
  logs: ApiResources<Log>;
  q: string;
}>();
</script>

<template>
  <Head title="Logs" />

  <Breadcrumbs>
    <Breadcrumb route="logs.index" text="Logs" />
  </Breadcrumbs>

  <InfinitePaginator :list="logs" loadable="logs" :filter="q">
    <template v-slot:default="{ item }">
      <ModalLink
        :href="route('logs.show', item.id)"
        class="block rounded-lg border border-border bg-surface-elevated p-4 hover:border-border-interactive hover:bg-surface-interactive-hover focus:ring-2 focus:ring-focus-ring focus:ring-offset-2 focus:outline-none"
      >
        <div class="flex items-center justify-between gap-4">
          <div class="min-w-0 flex-1">
            <div class="truncate font-medium text-text">
              {{ (item as (typeof logs.data)[0]).event }}
            </div>
            <div class="truncate text-sm text-text-muted">
              {{ (item as (typeof logs.data)[0]).creator }}
            </div>
          </div>
          <div
            class="shrink-0 text-sm text-text-subtle"
            :title="(item as (typeof logs.data)[0]).created_at"
          >
            {{ (item as (typeof logs.data)[0]).created_ago }}
          </div>
        </div>
      </ModalLink>
    </template>
  </InfinitePaginator>
</template>
