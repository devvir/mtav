<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import MaybeModal from '@/components/MaybeModal.vue';
import { _ } from '@/composables/useTranslations';
import ShowWrapper from '../shared/ShowWrapper.vue';
import IndexCard from './Crud/IndexCard.vue';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings

defineProps<{
  family: ApiResource<Family>;
}>();
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
          <div v-if="'name' in family.project" class="mt-2 text-sm text-text-subtle">
            <span>{{ _('Project') }}:</span> {{ family.project.name }}
          </div>
        </template>

        <template v-slot:content-before>
          <!-- ... -->
        </template>

        <template v-slot:content-after>
          <div class="mt-4 border-t border-border py-base text-right text-sm text-text-subtle">
              {{ _('Created') }}: {{ family.created_ago }}
          </div>
        </template>
      </IndexCard>
    </ShowWrapper>
  </MaybeModal>
</template>
