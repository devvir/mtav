<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import InfinitePaginator from '@/components/pagination/InfinitePaginator.vue';
import MembersFamiliesSwitch from '@/components/switches/MembersFamiliesSwitch.vue';
import IndexCard from './Crud/IndexCard.vue';

defineProps<{
  admins: ApiResources<Admin>;
  q: string;
}>();

const gridColsOverrides = {
  xl: '@xl:grid-cols-[repeat(auto-fill,minmax(400px,1fr))]',
};
</script>

<template>
  <Head title="Members" />

  <Breadcrumbs>
    <Breadcrumb route="members.index" text="Members" />
  </Breadcrumbs>

  <InfinitePaginator :list="admins" loadable="admins" :filter="q" :gridColsOverrides="gridColsOverrides">
    <template v-slot:search-right>
      <MembersFamiliesSwitch />
    </template>

    <template v-slot:default="{ item }">
      <IndexCard :admin="item as (typeof admins.data)[0]" />
    </template>
  </InfinitePaginator>
</template>
