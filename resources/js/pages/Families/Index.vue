<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import InfinitePaginator from '@/components/pagination/InfinitePaginator.vue';
import MembersFamiliesSwitch from '@/components/switches/MembersFamiliesSwitch.vue';
import IndexCard from './Crud/IndexCard.vue';

defineProps<{
  families: ApiResources<Family>;
  q: string;
}>();

const gridColsOverrides = {
  xl: 'xl:grid-cols-[repeat(auto-fill,minmax(440px,1fr))]',
};
</script>

<template>
  <Head title="Families" />

  <Breadcrumbs>
    <Breadcrumb route="families.index" text="Families" />
  </Breadcrumbs>

  <InfinitePaginator :list="families" loadable="families" :filter="q" :gridColsOverrides="gridColsOverrides">
    <template v-slot:search-right>
      <MembersFamiliesSwitch />
    </template>

    <template v-slot:default="{ item }">
      <IndexCard :family="item as (typeof families.data)[0]" />
    </template>
  </InfinitePaginator>
</template>
