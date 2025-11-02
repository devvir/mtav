<script setup lang="ts">
import Card from '@/components/shared/Card.vue';
import { currentProject } from '@/composables/useProjects';
import { _ } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';
import { ModalLink, useModal } from '@inertiaui/modal-vue';
import { Edit3Icon } from 'lucide-vue-next';
import SelectDeselect from './SelectDeselect.vue';
import { HTMLAttributes } from 'vue';

const props = defineProps<{
  project: ApiResource<Required<Project>>;
  class?: HTMLAttributes['class'];
}>();

const projectLink = computed(() =>
  props.project.allows.view
    ? route('projects.show', props.project.id)
    : route('home')
);

</script>

<template>
  <Card
    class=""
    :class="
      cn(props.class, {
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
            <p class="text-xs font-medium uppercase tracking-wide text-text-subtle">
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

        <ModalLink
          v-if="project.allows.update"
          paddingClasses="p-8"
          :href="route('projects.edit', project.id)"
          class="shrink-0 rounded-lg bg-surface-interactive p-3 ring-2 ring-border transition-all hover:bg-surface-interactive-hover hover:ring-border-strong focus:outline-0 focus:ring-2 focus:ring-focus-ring focus:ring-offset-2"
          :title="_('Edit')"
        >
          <Edit3Icon class="h-5 w-5" />
        </ModalLink>
      </div>
    </template>

    <section class="my-4 border-b border-border-subtle pb-4">
      <div class="mb-3 text-xs font-medium uppercase tracking-wide text-text-subtle">
        {{ _('Families') }} ({{ project.families_count }})
      </div>

      <Link class="flex flex-wrap gap-3" :href="route('families.index')">
        <img
          v-for="family in project.families"
          :key="family.id"
          :src="family.avatar"
          :title="family.name"
          alt="avatar"
          class="size-8 rounded ring-2 ring-border transition-transform hover:scale-110"
        />

        <div v-if="project.families_count > project.families.length" class="flex items-center">
          <svg class="size-8 fill-current text-text-subtle" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="2" />
            <circle cx="19" cy="12" r="2" />
            <circle cx="5" cy="12" r="2" />
          </svg>
        </div>
      </Link>
    </section>

    <section class="my-4 border-b border-border-subtle pb-4">
      <div class="mb-3 text-xs font-medium uppercase tracking-wide text-text-subtle">
        {{ _('Members') }} ({{ project.members_count }})
      </div>

      <Link class="flex flex-wrap gap-3" :href="route('members.index')">
        <img
          v-for="member in project.members"
          :key="member.id"
          :src="member.avatar"
          :title="member.name"
          alt="avatar"
          class="size-8 rounded-full ring-2 ring-border transition-transform hover:scale-110"
        />

        <div v-if="project.members_count > project.members.length" class="flex items-center">
          <svg class="size-8 fill-current text-text-subtle" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="2" />
            <circle cx="19" cy="12" r="2" />
            <circle cx="5" cy="12" r="2" />
          </svg>
        </div>
      </Link>
    </section>

    <section class="my-4 border-b border-border-subtle pb-4">
      <div class="mb-3 text-xs font-medium uppercase tracking-wide text-text-subtle">
        {{ _('Admins') }} ({{ project.admins_count }})
      </div>

      <Link class="flex flex-wrap gap-3" :href="route('admins.index')">
        <img
          v-for="admin in project.admins"
          :key="admin.id"
          :src="admin.avatar"
          :title="admin.name"
          alt="avatar"
          class="size-8 rounded ring-2 ring-border transition-transform hover:scale-110"
        />

        <div v-if="project.admins_count > project.admins.length" class="flex items-center">
          <svg class="size-8 fill-current text-text-subtle" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="2" />
            <circle cx="19" cy="12" r="2" />
            <circle cx="5" cy="12" r="2" />
          </svg>
        </div>
      </Link>
    </section>

    <section class="mt-4">
      <SelectDeselect :project="project" :selected="currentProject?.id === project.id" />
    </section>
  </Card>
</template>
