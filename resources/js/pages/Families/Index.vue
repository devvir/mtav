<script setup lang="ts">
import IndexPage from '@/components/entities/IndexPage.vue';
import MembersFamiliesSwitch from '@/components/switches/MembersFamiliesSwitch.vue';

defineProps<{
  families: ApiResources<Family>;
  q: string;
}>();

const gridColsOverrides = {
  xl: '@xl:grid-cols-[repeat(auto-fill,minmax(440px,1fr))]',
};
</script>

<template>
  <IndexPage entity="family" :resources="families" :q :gridColsOverrides>
    <template v-slot:search-right>
      <MembersFamiliesSwitch />
    </template>
  </IndexPage>
</template>
