<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';

defineProps<{
  family: Family;
}>();
</script>

<template>
  <Head title="Update Family" />

  <Breadcrumbs>
    <Breadcrumb route="families.index" text="Families" />
    <Breadcrumb route="families.show" :params="family.id">{{ family.name }}</Breadcrumb>
    <Breadcrumb route="families.edit" :params="family.id" text="Edit" />
  </Breadcrumbs>
</template>
