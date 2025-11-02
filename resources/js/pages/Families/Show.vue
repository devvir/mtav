<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import MaybeModal from '@/components/MaybeModal.vue';
import { _, locale } from '@/composables/useTranslations';
import ShowWrapper from '../shared/ShowWrapper.vue';
import IndexCard from './Crud/IndexCard.vue';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings

defineProps<{
  family: ApiResource<Family>;
}>();

const longDate = (date: string) =>
  // Intl requires RFC-5646 for the locale (i.e. hyphen not underscore)
  new Intl.DateTimeFormat(locale.value.replace('_', '-'), {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }).format(new Date(date));
</script>

<template>
  <Head :title="family.name" no-translation />

  <Breadcrumbs>
    <Breadcrumb route="families.index" text="Families" />
    <Breadcrumb route="families.show" :params="family.id">{{ family.name }}</Breadcrumb>
  </Breadcrumbs>

  <MaybeModal panelClasses="modalPanel">
    <ShowWrapper>
      <IndexCard :family class="pt-12">
        <template v-slot:header>
          <div class="mt-2 text-right text-sm text-text-subtle">
            <span>{{ _('Project') }}:</span> {{ family.project.name }}
          </div>
        </template>

        <template v-slot:content-before>
          <!-- ... -->
        </template>

        <template v-slot:content-after>
          <div class="mt-4 border-t border-border py-base text-right text-sm text-text-subtle">
            <span>{{ _('Registered on') }}</span> {{ longDate(family.created_at) }}
          </div>
        </template>
      </IndexCard>
    </ShowWrapper>
  </MaybeModal>
</template>
