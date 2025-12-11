<script setup lang="ts">
import MemberFamilyFilters from '@/components/entities/_shared/MemberFamilyFilters.vue';
import IndexPage from '@/components/entities/IndexPage.vue';
import { useMemberGrouping } from '@/state/useMemberGrouping';

defineProps<{
  families?: ApiResources<Family>;
}>();

const { setMemberGrouping } = useMemberGrouping();
</script>

<template>
  <IndexPage entity="family" :resources="families" card-size="lg">
    <MemberFamilyFilters :grouped="true" @input="setMemberGrouping" />
  </IndexPage>
</template>
