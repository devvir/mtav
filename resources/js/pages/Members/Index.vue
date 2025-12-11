<script setup lang="ts">
import MemberFamilyFilters from '@/components/entities/_shared/MemberFamilyFilters.vue';
import IndexPage from '@/components/entities/IndexPage.vue';
import { useMemberGrouping } from '@/state/useMemberGrouping';

defineProps<{
  members?: ApiResources<Member>;
}>();

const { setMemberGrouping } = useMemberGrouping();
</script>

<template>
  <IndexPage entity="member" :resources="members">
    <MemberFamilyFilters :grouped="false" @input="setMemberGrouping" />
  </IndexPage>
</template>
