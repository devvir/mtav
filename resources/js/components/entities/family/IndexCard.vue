<script setup lang="ts">
import Ellipsis from '@/components/Ellipsis.vue';
import { Avatar } from '@/components/avatar';
import { EntityCard, CardContent, CardFooter, CardHeader, Counter } from '@/components/card';
import { ModalLink } from '@inertiaui/modal-vue';

defineProps<{
  family: ApiResource<Family>;
}>();
</script>

<template>
  <EntityCard :resource="family" entity="family" type="index" :dimmed="! family.members!.length">
    <CardHeader :title="family.name" avatar="sm" />

    <CardContent class="group flex-row items-center gap-wide">
      <ul v-if="family.members_count" class="flex flex-wrap gap-1 truncate">
        <li v-for="member in family.members.slice(0, 7)" :key="member.id">
          <ModalLink :href="route('members.show', member.id)" @click.stop>
            <Avatar :subject="member" :title="member.name" class="rounded-full m-1 shadow-xs shadow-card-elevated-foreground/30 hover:shadow-card-elevated-foreground/50 group-hover:text-text scale-110" size="sm" />
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

    <CardFooter />
  </EntityCard>
</template>
