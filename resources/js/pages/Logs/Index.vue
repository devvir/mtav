<script setup lang="ts">
// Copilot - pending review
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import MaybeModal from '@/components/MaybeModal.vue';
import { _, locale } from '@/composables/useTranslations';

defineProps<{
  logs: any[];
}>();

const formatDate = (date: string) =>
  new Intl.DateTimeFormat(locale.value.replace('_', '-'), {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(date));
</script>

<template>
  <Head title="Logs" />

  <Breadcrumbs>
    <Breadcrumb route="logs.index" text="Logs" />
  </Breadcrumbs>

  <MaybeModal>
    <div class="space-y-2">
      <div v-if="!logs.length" class="flex items-center justify-center p-8">
        <p class="text-muted-foreground">{{ _('No logs yet') }}</p>
      </div>

      <div v-else class="space-y-2">
        <a
          v-for="log in logs"
          :key="log.id"
          :href="route('logs.show', log.id)"
          class="block rounded-lg border p-3 hover:bg-accent"
        >
          <div class="flex items-center justify-between gap-4">
            <div class="flex-1">
              <div class="font-medium">{{ log.event }}</div>
              <div class="text-sm text-muted-foreground">
                {{ log.user?.name || _('System') }}
              </div>
            </div>
            <div class="text-sm text-muted-foreground">
              {{ formatDate(log.created_at) }}
            </div>
          </div>
        </a>
      </div>
    </div>
  </MaybeModal>
</template>
