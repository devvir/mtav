<script setup lang="ts">
import PersonAvatar from '@/components/feedback/PersonAvatar.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import type { Family } from '@/types/models';
import { Link, usePage } from '@inertiajs/vue3';
import { Home, UserPlus } from 'lucide-vue-next';

const props = defineProps<{ family: Family }>();

const isOwn = computed(() => usePage().props.project?.family?.id === props.family.id);
</script>

<template>
  <PageHeader
    :title="family.name"
    :description="family.unit_type?.name"
    :back="{ href: route('families.index'), label: _('Families') }"
  >
    <template v-if="isOwn" #actions>
      <Button as-child
        ><Link :href="route('invitations.index')"
          ><UserPlus /> {{ _('Invite someone') }}</Link
        ></Button
      >
    </template>
  </PageHeader>

  <p
    v-if="family.unit"
    class="mb-6 flex items-center gap-2 rounded-2xl bg-success-subtle p-4 text-success-subtle-foreground"
  >
    <Home class="size-5" />
    {{ _('Received unit {unit} in the lottery.', { unit: family.unit.identifier }) }}
  </p>

  <ul class="grid gap-3 sm:grid-cols-2">
    <li v-for="person in family.members" :key="person.id">
      <Link
        :href="route('people.show', person.id)"
        class="flex items-center gap-3 rounded-2xl border-2 border-border bg-surface p-3 hover:border-border-interactive"
      >
        <PersonAvatar :name="person.name" :src="person.avatar" />
        <span class="font-semibold">{{ person.name }}</span>
      </Link>
    </li>
  </ul>
  <p v-if="!family.members?.length" class="py-8 text-center text-text-muted">
    {{ _('Nobody has joined this family yet.') }}
  </p>
</template>
