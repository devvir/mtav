<script setup lang="ts">
import IndexPage from '@/components/entities/IndexPage.vue';
import MembersFamiliesSwitch from '@/components/switches/MembersFamiliesSwitch.vue';

defineProps<{
  families: ApiResources<Family>;
  q?: string;
}>();
</script>

<template>
  <IndexPage entity="family" :resources="families" :q card-size="lg">
    <template v-slot:search-right>
      <MembersFamiliesSwitch />
    </template>
  </IndexPage>
</template>
