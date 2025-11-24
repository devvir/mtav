<script setup lang="ts">
import MemberFamilyFilters from '@/components/entities/_shared/MemberFamilyFilters.vue';
import IndexPage from '@/components/entities/IndexPage.vue';

defineProps<{
  families?: ApiResources<Family>;
}>();
</script>

<template>
  <IndexPage entity="family" :resources="families" card-size="lg">
    <MemberFamilyFilters :grouped="true" />
  </IndexPage>
</template>
