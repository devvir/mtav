<script setup lang="ts">
// Copilot - pending review
import CardBox from '@/components/shared/CardBox.vue';
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import ShowWrapper from '../shared/ShowWrapper.vue';
import MaybeModal from '@/components/MaybeModal.vue';
import { _, locale } from '@/composables/useTranslations';

defineProps<{
  log: any;
}>();

const formatDate = (date: string) =>
  new Intl.DateTimeFormat(locale.value.replace('_', '-'), {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(date));
</script>

<template>
  <Head :title="`Log #${log.id}`" />

  <Breadcrumbs>
    <Breadcrumb route="logs.index" text="Logs" />
    <Breadcrumb :route="route('logs.show', log.id)" :text="`Log #${log.id}`" />
  </Breadcrumbs>

  <MaybeModal>
    <ShowWrapper :resource-id="log.id">
      <CardBox>
        <div class="space-y-4">
          <div>
            <div class="text-sm font-medium text-text-subtle">{{ _('Event') }}</div>
            <div class="text-text">{{ log.event }}</div>
          </div>

          <div>
            <div class="text-sm font-medium text-text-subtle">{{ _('User') }}</div>
            <div class="text-text">{{ log.user?.name || _('System') }}</div>
          </div>

          <div>
            <div class="text-sm font-medium text-text-subtle">{{ _('Date') }}</div>
            <div class="text-text">{{ formatDate(log.created_at) }}</div>
          </div>
        </div>
      </CardBox>
    </ShowWrapper>
  </MaybeModal>
</template>
