<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import MaybeModal from '@/components/MaybeModal.vue';
import IndexCard from './Crud/IndexCard.vue';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings

defineProps<{
  family: Family;
}>();
</script>

<template>
  <Head :title="family.name" no-translation />

  <Breadcrumbs>
    <Breadcrumb route="families.index" text="Families" />
    <Breadcrumb route="families.show" :params="family.id">{{ family.name }}</Breadcrumb>
  </Breadcrumbs>

  <MaybeModal panelClasses="modalPanel close-left">
    <IndexCard :family class="w-full" />
  </MaybeModal>
</template>
