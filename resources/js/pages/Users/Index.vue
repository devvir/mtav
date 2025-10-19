<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import InfinitePaginator from '@/components/pagination/InfinitePaginator.vue';
import MembersFamiliesSwitch from '@/components/switches/MembersFamiliesSwitch.vue';
import IndexCard from './Crud/IndexCard.vue';

defineProps<{
  members: ApiResources<User>;
  q: string;
}>();

const gridColsOverrides = {
  xl: '@xl:grid-cols-[repeat(auto-fill,minmax(400px,1fr))]',
};
</script>

<template>
  <Head title="Members" />

  <Breadcrumbs>
    <Breadcrumb route="users.index" text="Members" />
  </Breadcrumbs>

  <InfinitePaginator :list="members" loadable="members" :filter="q" :gridColsOverrides="gridColsOverrides">
    <template v-slot:search-right>
      <MembersFamiliesSwitch />
    </template>

    <template v-slot:default="{ item }">
      <IndexCard :user="item as (typeof members.data)[0]" />
    </template>
  </InfinitePaginator>
</template>
