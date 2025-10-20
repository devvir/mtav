<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import InfinitePaginator from '@/components/pagination/InfinitePaginator.vue';
import IndexCard from './Crud/IndexCard.vue';

defineProps<{
  admins: ApiResources<Admin>;
  q: string;
}>();

const gridColsOverrides = {
  lg: '@lg:grid-cols-[repeat(auto-fill,minmax(300px,1fr))]',
  xl: '@xl:grid-cols-[repeat(auto-fill,minmax(320px,1fr))]',
  xl2: '@2xl:grid-cols-[repeat(auto-fill,minmax(350px,1fr))]',
  xl4: '@4xl:grid-cols-[repeat(auto-fill,minmax(410px,1fr))]',
  xl6: '@6xl:grid-cols-[repeat(auto-fill,minmax(500px,1fr))]',
};
</script>

<template>
  <Head title="Admins" />

  <Breadcrumbs> <Breadcrumb route="admins.index" text="Admins" /> </Breadcrumbs>

  <InfinitePaginator :list="admins" loadable="admins" :filter="q" :gridColsOverrides>
    <template v-slot:default="{ item }">
      <IndexCard :admin="item as (typeof admins.data)[0]" />
    </template>
  </InfinitePaginator>
</template>
