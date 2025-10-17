<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import MaybeModal from '@/components/MaybeModal.vue';
import { locale } from '@/composables/useTranslations';
import ShowWrapper from '../shared/ShowWrapper.vue';
import IndexCard from './Crud/IndexCard.vue';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings

defineProps<{
  family: Family;
}>();

const longDate = (date: string) =>
  new Intl.DateTimeFormat(locale.value, {
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

  <MaybeModal panelClasses="modalPanel close-left">
    <ShowWrapper>
      <IndexCard :family>
        <template v-slot:header>
          <div class="ml-4 text-sm text-muted-foreground/50">Project: {{ family.project.name }}</div>
        </template>

        <template v-slot:content-before>
          <!-- ... -->
        </template>

        <template v-slot:content-after>
          <div class="mt-4 border-t border-muted py-base text-muted-foreground/80">
            Registrada el {{ longDate(family.created_at) }}
          </div>
        </template>
      </IndexCard>
    </ShowWrapper>
  </MaybeModal>
</template>
