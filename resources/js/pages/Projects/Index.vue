<script setup lang="ts">
import Badge from '@/components/feedback/Badge.vue';
import DateTime from '@/components/feedback/DateTime.vue';
import EmptyState from '@/components/feedback/EmptyState.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import type { Project } from '@/types/models';
import { Link, router } from '@inertiajs/vue3';
import { FolderPlus, Mail, ShieldCheck, Users } from 'lucide-vue-next';

defineProps<{
  projects: Project[];
  invitations: {
    id: number;
    project: string;
    family: string | null;
    is_admin: boolean;
    inviter: string | null;
    expires_at: string;
  }[];
  canCreate: boolean;
}>();

const respond = (id: number, accept: boolean) =>
  router.post(route('invitations.respond', id), { accept });
</script>

<template>
  <PageHeader :title="_('Your projects')">
    <template v-if="canCreate" #actions>
      <Button as-child>
        <Link :href="route('projects.create')"><FolderPlus /> {{ _('Start a project') }}</Link>
      </Button>
    </template>
  </PageHeader>

  <section v-if="invitations.length" class="mb-8 space-y-3">
    <h2 class="text-lg font-semibold">{{ _('Invitations waiting for you') }}</h2>
    <article
      v-for="invitation in invitations"
      :key="invitation.id"
      class="flex flex-col gap-4 rounded-2xl border-2 border-info/40 bg-info-subtle p-4 sm:flex-row sm:items-center"
    >
      <Mail class="size-8 shrink-0 text-info" />
      <div class="flex-1">
        <p class="font-semibold">{{ invitation.project }}</p>
        <p class="text-sm text-text-muted">
          <template v-if="invitation.is_admin">{{ _('As an admin') }}</template>
          <template v-if="invitation.is_admin && invitation.family"> · </template>
          <template v-if="invitation.family">{{
            _('In the {family} family', { family: invitation.family })
          }}</template>
          <template v-if="invitation.inviter">
            · {{ _('From {name}', { name: invitation.inviter }) }}</template
          >
          · {{ _('Until') }} <DateTime :value="invitation.expires_at" :time="false" />
        </p>
      </div>
      <div class="flex gap-2">
        <Button variant="outline" @click="respond(invitation.id, false)">{{ _('Decline') }}</Button>
        <Button @click="respond(invitation.id, true)">{{ _('Join') }}</Button>
      </div>
    </article>
  </section>

  <div v-if="projects.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <Link
      v-for="project in projects"
      :key="project.id"
      :href="route('projects.show', project.id)"
      class="group flex flex-col gap-3 rounded-2xl border-2 border-border bg-surface p-5 transition hover:border-border-interactive hover:shadow-md"
    >
      <div class="flex items-start justify-between gap-2">
        <h2 class="text-lg font-semibold group-hover:text-text-link">{{ project.name }}</h2>
        <Badge v-if="project.is_admin" tone="info"
          ><ShieldCheck class="size-3" /> {{ _('Admin') }}</Badge
        >
      </div>
      <p class="line-clamp-2 text-text-muted">{{ project.description }}</p>
      <div class="mt-auto flex items-center gap-4 text-sm text-text-muted">
        <span class="flex items-center gap-1"
          ><Users class="size-4" />
          {{ _('{count} people', { count: project.members_count ?? 0 }) }}</span
        >
        <Badge v-if="!project.is_approved" tone="warning">{{ _('Awaiting approval') }}</Badge>
      </div>
    </Link>
  </div>

  <EmptyState
    v-else-if="!invitations.length"
    :icon="FolderPlus"
    :title="_('You are not in any project yet')"
    :message="
      _(
        'When someone invites you, the invitation appears here. You can also start a project of your own.',
      )
    "
  >
    <Button v-if="canCreate" as-child>
      <Link :href="route('projects.create')">{{ _('Start a project') }}</Link>
    </Button>
  </EmptyState>
</template>
