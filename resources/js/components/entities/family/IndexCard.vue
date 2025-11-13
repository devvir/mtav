<script setup lang="ts">
import Ellipsis from '@/components/Ellipsis.vue';
import { Avatar } from '@/components/avatar';
import { Card, CardContent, CardFooter, CardHeader, Counter } from '@/components/card';

defineProps<{
  family: ApiResource<Family>;
}>();
</script>

<template>
  <Card :resource="family" entity="family" type="index">
    <CardHeader :title="family.name" avatar="md" />

    <CardContent class="flex-row items-center gap-wide">
      <Counter :count="family.members_count" singular="Member" plural="Members" size="lg" />

      <ul v-if="family.members_count" class="flex flex-wrap gap-2 truncate">
        <li v-for="member in family.members.slice(0, 7)" :key="member.id">
          <Avatar :subject="member" size="sm" />
        </li>

        <Ellipsis v-if="family.members_count > 7" />
      </ul>
    </CardContent>

    <CardFooter />
  </Card>
</template>
