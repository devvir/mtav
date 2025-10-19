<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import MaybeModal from '@/components/MaybeModal.vue';
import { _, locale } from '@/composables/useTranslations';
import { ModalLink } from '@inertiaui/modal-vue';
import { Edit3Icon } from 'lucide-vue-next';
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

  <MaybeModal panelClasses="modalPanel">
    <ShowWrapper>
      <IndexCard :family class="pt-12">
        <template v-slot:header>
          <div class="mt-2 ml-4 text-sm text-muted-foreground/80">
            <span>{{ _('Project') }}:</span> {{ family.project.name }}
          </div>
        </template>

        <template v-slot:content-before>
          <!-- ... -->
        </template>

        <template v-slot:content-after>
          <div class="mt-4 border-t border-muted py-base text-right text-sm text-muted-foreground/50">
            <span>{{ _('Registered on') }}</span> {{ longDate(family.created_at) }}
          </div>

          <ModalLink
            class="flex items-center-safe justify-end gap-2 border-t pt-base"
            paddingClasses="p-8"
            :href="route('families.edit', family.id)"
          >
            {{ _('Edit Family') }} <Edit3Icon />
          </ModalLink>
        </template>
      </IndexCard>
    </ShowWrapper>
  </MaybeModal>
</template>
