<script setup lang="ts">
import { Avatar } from '@/components/avatar';
import EditButton from '@/components/EditButton.vue';
import Ellipsis from '@/components/Ellipsis.vue';
import Card from '@/components/shared/Card.vue';
import { currentProject } from '@/composables/useProjects';
import { _ } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';
import { ModalLink, useModal } from '@inertiaui/modal-vue';
import type { HTMLAttributes } from 'vue';
import SelectDeselect from './SelectDeselect.vue';

const props = defineProps<{
  project: ApiResource<Required<Project>>;
  class?: HTMLAttributes['class'];
}>();

const projectLink = computed(() =>
  props.project.allows.view ? route('projects.show', props.project.id) : route('dashboard'),
);
</script>

<template>
  <Card
    class=""
    :class="
      cn($props.class, {
        'shadow-lg ring-2 ring-interactive': currentProject?.id === project.id,
        'opacity-60': !project.active,
      })
    "
  >
    <template v-slot:header>
      <div class="flex items-start justify-between gap-4">
        <ModalLink
          class="block min-w-0 flex-1 cursor-pointer focus:outline-0"
          :class="useModal() ? 'pointer-events-none' : ''"
          :href="projectLink"
        >
          <div class="space-y-2">
            <p class="truncate text-xl font-semibold text-text" :title="project.name">
              {{ project.name }}
            </p>
            <p class="text-xs font-medium tracking-wide text-text-subtle uppercase">
              {{ project.active ? _('Active') : _('Inactive') }}
            </p>

            <p
              class="overflow-hidden text-left text-sm leading-relaxed text-text-muted"
              :class="{ 'line-clamp-2': !useModal() }"
            >
              {{ project.description }}
            </p>
          </div>
        </ModalLink>

        <EditButton :resource="project" route-name="projects.edit" />
      </div>
    </template>

    <section class="my-4 border-b border-border-subtle pb-4">
      <div class="mb-3 text-xs font-medium tracking-wide text-text-subtle uppercase">
        {{ _('Families') }} ({{ project.families_count }})
      </div>

      <Link class="flex flex-wrap gap-3" :href="route('families.index')">
        <Avatar
          v-for="family in project.families"
          :key="family.id"
          :subject="family"
          size="sm"
          class="ring-2 ring-border transition-transform hover:scale-110"
        />

        <Ellipsis v-if="project.families_count > project.families.length" />
      </Link>
    </section>

    <section class="my-4 border-b border-border-subtle pb-4">
      <div class="mb-3 text-xs font-medium tracking-wide text-text-subtle uppercase">
        {{ _('Members') }} ({{ project.members_count }})
      </div>

      <Link class="flex flex-wrap gap-3" :href="route('members.index')">
        <Avatar
          v-for="member in project.members"
          :key="member.id"
          :subject="member"
          size="sm"
          class="rounded-full ring-2 ring-border transition-transform hover:scale-110"
        />

        <Ellipsis v-if="project.members_count > project.members.length" />
      </Link>
    </section>

    <section class="my-4 border-b border-border-subtle pb-4">
      <div class="mb-3 text-xs font-medium tracking-wide text-text-subtle uppercase">
        {{ _('Admins') }} ({{ project.admins_count }})
      </div>

      <Link class="flex flex-wrap gap-3" :href="route('admins.index')">
        <Avatar
          v-for="admin in project.admins"
          :key="admin.id"
          :subject="admin"
          size="sm"
          class="ring-2 ring-border transition-transform hover:scale-110"
        />

        <Ellipsis v-if="project.admins_count > project.admins.length" />
      </Link>
    </section>

    <section class="mt-4">
      <SelectDeselect :project="project" :selected="currentProject?.id === project.id" />
    </section>
  </Card>
</template>
