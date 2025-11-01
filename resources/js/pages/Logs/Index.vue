<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import MaybeModal from '@/components/MaybeModal.vue';
import { ModalLink } from '@inertiaui/modal-vue';
import { _ } from '@/composables/useTranslations';

defineProps<{
  logs: ApiResources<Log>;
}>();
</script>

<template>
  <Head title="Logs" />

  <Breadcrumbs>
    <Breadcrumb route="logs.index" text="Logs" />
  </Breadcrumbs>

  <MaybeModal>
    <div class="space-y-2">
      <div v-if="!logs.data.length" class="flex items-center justify-center p-8">
        <p class="text-text-muted">{{ _('No logs yet') }}</p>
      </div>

      <div v-else class="space-y-2">
        <ModalLink
          v-for="log in logs.data"
          :key="log.id"
          :href="route('logs.show', log.id)"
          class="block rounded-lg border border-border bg-surface-elevated p-3 hover:border-border-interactive hover:bg-surface-interactive-hover focus:outline-none focus:ring-2 focus:ring-focus-ring focus:ring-offset-2"
        >
          <div class="flex items-center justify-between gap-4">
            <div class="flex-1">
              <div class="font-medium text-text">{{ log.event }}</div>
              <div class="text-sm text-text-muted">
                {{ log.creator }}
              </div>
            </div>
            <div class="text-sm text-text-subtle" :title="log.created_at">
              {{ log.created_ago }}
            </div>
          </div>
        </ModalLink>
      </div>
    </div>
  </MaybeModal>
</template>
