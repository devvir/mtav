<script setup lang="ts">
import IndexPage from '@/components/entities/IndexPage.vue';
import MembersFamiliesSwitch from '@/components/switches/MembersFamiliesSwitch.vue';

defineProps<{
  members: ApiResources<Member>;
  q?: string;
}>();
</script>

<template>
  <IndexPage entity="member" :resources="members" :q>
    <template v-slot:search-right>
      <MembersFamiliesSwitch />
    </template>
  </IndexPage>
</template>
