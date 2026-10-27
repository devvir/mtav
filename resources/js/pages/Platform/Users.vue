<script setup lang="ts">
import Badge from '@/components/feedback/Badge.vue';
import DateTime from '@/components/feedback/DateTime.vue';
import Pagination from '@/components/feedback/Pagination.vue';
import SearchField from '@/components/feedback/SearchField.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { confirm } from '@/composables/useDialogs';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import type { Paginated } from '@/types/models';
import { router, usePage } from '@inertiajs/vue3';

type Row = {
  id: number;
  name: string;
  email: string;
  is_superadmin: boolean;
  verified: boolean;
  projects_count: number;
  created_at: string;
};

defineProps<{ users: Paginated<Row>; filters: { q: string | null; superadmins: boolean } }>();

const me = computed(() => usePage().props.auth.user?.id);

async function toggle(user: Row) {
  const grant = !user.is_superadmin;
  const ok = await confirm({
    title: grant
      ? _('Make {name} a superadmin?', { name: user.name })
      : _("Remove {name}'s superadmin role?", { name: user.name }),
    message: grant ? _('Superadmins see and manage every project on the platform.') : undefined,
    confirmLabel: grant ? _('Make superadmin') : _('Remove the role'),
    danger: !grant,
  });
  if (ok) router.put(route('admin.users.superadmin', user.id), { grant }, { preserveScroll: true });
}
</script>

<template>
  <PageHeader
    :title="_('Accounts')"
    :description="_('Everyone with an account on the platform.')"
  />

  <SearchField
    :model-value="filters.q"
    :placeholder="_('Search by name or email')"
    class="mb-4 max-w-md"
  />

  <ul class="divide-y divide-border-subtle rounded-2xl border-2 border-border bg-surface">
    <li
      v-for="user in users.data"
      :key="user.id"
      class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center"
    >
      <div class="min-w-0 flex-1">
        <p class="flex flex-wrap items-center gap-2 font-semibold">
          {{ user.name }}
          <Badge v-if="user.is_superadmin" tone="info">{{ _('Superadmin') }}</Badge>
          <Badge v-if="!user.verified" tone="warning">{{ _('Email not confirmed') }}</Badge>
        </p>
        <p class="truncate text-sm text-text-muted">
          {{ user.email }} · {{ _('{count} projects', { count: user.projects_count }) }} ·
          <DateTime :value="user.created_at" :time="false" />
        </p>
      </div>
      <Button v-if="user.id !== me" variant="outline" size="sm" @click="toggle(user)">
        {{ user.is_superadmin ? _('Remove superadmin') : _('Make superadmin') }}
      </Button>
    </li>
  </ul>

  <Pagination :page="users" />
</template>
