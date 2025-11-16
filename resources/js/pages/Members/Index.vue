<script setup lang="ts">
import MemberFamilyFilters from '@/components/entities/_shared/MemberFamilyFilters.vue';
import IndexPage from '@/components/entities/IndexPage.vue';

defineProps<{
  members: ApiResources<Member>;
}>();
</script>

<template>
  <IndexPage entity="member" :resources="members">
    <MemberFamilyFilters :grouped="false" />
  </IndexPage>
</template>
