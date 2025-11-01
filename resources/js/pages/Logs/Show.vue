<script setup lang="ts">
import CardBox from '@/components/shared/CardBox.vue';
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import ShowWrapper from '../shared/ShowWrapper.vue';
import MaybeModal from '@/components/MaybeModal.vue';
import { _ } from '@/composables/useTranslations';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings

defineProps<{
  log: Log;
}>();
</script>

<template>
  <Head :title="`Log #${log.id}`" />

  <Breadcrumbs>
    <Breadcrumb route="logs.index" text="Logs" />
    <Breadcrumb route="logs.show" :params="log.id" :text="`Log #${log.id}`" />
  </Breadcrumbs>

  <MaybeModal>
    <ShowWrapper :resource-id="log.id">
      <CardBox>
        <div class="space-y-4 p-6">
          <div>
            <div class="text-sm font-medium text-text-subtle">{{ _('Event') }}</div>
            <div class="text-text">{{ log.event }}</div>
          </div>

          <div>
            <div class="text-sm font-medium text-text-subtle">{{ _('User') }}</div>
            <div v-if="log.creator_href" class="text-text">
              <Link :href="log.creator_href" class="text-primary hover:underline">
                {{ log.creator }}
              </Link>
            </div>
            <div v-else class="text-text">{{ log.creator }}</div>
          </div>

          <div>
            <div class="text-xs font-medium text-text-subtle">{{ _('Date') }}</div>
            <div class="text-sm text-text-subtle" :title="log.created_at">{{ log.created_ago }}</div>
          </div>
        </div>
      </CardBox>
    </ShowWrapper>
  </MaybeModal>
</template>
