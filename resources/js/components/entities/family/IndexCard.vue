<script setup lang="ts">
import Ellipsis from '@/components/Ellipsis.vue';
import { Avatar } from '@/components/avatar';
import { CardContent, CardHeader, Counter, EntityCard } from '@/components/card';
import { ModalLink } from '@inertiaui/modal-vue';
import FamilyFooter from './shared/FamilyFooter.vue';

defineProps<{
  family: ApiResource<Family>;
}>();
</script>

<template>
  <EntityCard :resource="family" entity="family" type="index" :dimmed="!family.members!.length">
    <CardHeader :title="family.name" avatar="sm" />

    <CardContent class="group flex-row items-center gap-wide">
      <ul v-if="family.members_count" class="flex flex-wrap gap-1 truncate">
        <li v-for="member in family.members.slice(0, 7)" :key="member.id">
          <ModalLink :href="route('members.show', member.id)" @click.stop>
            <Avatar
              :subject="member"
              :title="member.name"
              class="m-1 scale-110 rounded-full shadow-xs shadow-card-elevated-foreground/30 group-hover:text-text hover:shadow-card-elevated-foreground/50"
              size="sm"
            />
          </ModalLink>
        </li>

        <Ellipsis v-if="family.members_count > 7" />
      </ul>

      <Counter
        :count="family.members_count"
        singular="Member"
        plural="Members"
        size="lg"
        class="ml-auto text-right"
      />
    </CardContent>

    <FamilyFooter :family />
  </EntityCard>
</template>
