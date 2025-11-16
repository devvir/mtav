<script setup lang="ts">
import { Card, CardFooter, CardHeader } from '@/components/card';
import { _ } from '@/composables/useTranslations';
import { ModalLink } from '@inertiaui/modal-vue';

defineProps<{
  member: ApiResource<Member>;
}>();
</script>

<template>
  <Card :resource="member" entity="member" type="index">
    <CardHeader :title="member.name" avatar="lg">
      <span class="pr-1 text-text/30">{{ _('Family') }}</span>
      <ModalLink :href="route('families.show', member.family.id)">
        <span class="text-text-link hover:text-text-link-hover">
          {{ member.family?.name }}
        </span>
      </ModalLink>
    </CardHeader>

    <CardFooter />
  </Card>
</template>
