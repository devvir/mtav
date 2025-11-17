<script setup lang="ts">
import { Avatar } from '@/components/avatar';
import { EntityCard, CardContent, CardFooter, CardHeader, CreatedMeta } from '@/components/card';
import ContentGrid from '@/components/card/snippets/ContentGrid.vue';
import { _ } from '@/composables/useTranslations';
import { ModalLink } from '@inertiaui/modal-vue';

defineProps<{
  family: ApiResource<Family>;
}>();
</script>

<template>
  <EntityCard :resource="family" entity="family" type="show">
    <CardHeader :title="family.name" :kicker="_('Family')" avatar="xl" />

    <CardContent>
      <ContentGrid min-width="35cqw">
        <ModalLink
          v-for="member in family.members"
          :key="member.id"
          class="group block rounded-lg text-text transition-all hover:bg-surface-interactive-hover focus:ring-2 focus:ring-focus-ring focus:ring-offset-2 focus:ring-offset-focus-ring-offset focus:outline-none"

          :href="route('members.show', member.id)"
          :title="member.name" prefetch="click"
        >
          <div class="flex items-center justify-start gap-3">
            <Avatar :subject="member" size="sm" class="rounded-full ring-2 ring-border" />
            <div class="truncate text-sm font-medium group-hover:text-text-link" :title="member.name">
              {{ member.name }}
            </div>
          </div>
        </ModalLink>
      </ContentGrid>
    </CardContent>

    <CardFooter class="flex justify-between text-xs">
      <span>{{ family.unit_type?.name || _('No unit type') }}</span>

      <CreatedMeta />
    </CardFooter>
  </EntityCard>
</template>
