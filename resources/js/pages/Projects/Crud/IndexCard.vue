<script setup lang="ts">
import Card from '@/components/shared/Card.vue';
import { currentProject } from '@/composables/useProjects';
import { _ } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';
import { ModalLink, useModal } from '@inertiaui/modal-vue';
import { Edit3Icon } from 'lucide-vue-next';
import SelectDeselect from './SelectDeselect.vue';

const props = defineProps<{
  project: ApiResource<Required<Project>>;
  class?: any;
}>();
</script>

<template>
  <Card
    class=""
    :class="
      cn(props.class, {
        'shadow-none ring ring-accent': currentProject?.id === project.id,
        '-z-1 opacity-60': !project.active,
      })
    "
  >
    <template v-slot:header>
      <ModalLink
        class="block max-w-[calc(100vw-5rem)] cursor-pointer text-right"
        :class="useModal() ? 'pointer-events-none' : 'h-28'"
        :href="route('projects.show', project.id)"
      >
        <p class="truncate text-xl" :title="project.name">
          {{ project.name }}
        </p>
        <p class="text-xs leading-wide tracking-wide text-muted-foreground/60">
          <span class="uppercase">{{ project.active ? _('Active') : _('Inactive') }}</span>
        </p>

        <p
          class="mt-base-y overflow-hidden text-left text-sm text-foreground/75"
          :class="{ 'line-clamp-2': !useModal() }"
        >
          {{ project.description }}
        </p>
      </ModalLink>

      <ModalLink
        v-if="project.allows.update"
        class="flex items-center-safe justify-end gap-2 pt-base"
        paddingClasses="p-8"
        :href="route('projects.edit', project.id)"
      >
        {{ _('Edit') }} <Edit3Icon />
      </ModalLink>
    </template>

    <section class="my-wide-y border-b border-foreground/10 pb-wide-y">
      <div class="mb-base flex-1 text-right text-xs">{{ _('Families') }} ({{ project.families_count }})</div>

      <Link class="flex flex-wrap gap-4" :href="route('families.index')">
        <img
          v-for="family in project.families"
          :key="family.id"
          :src="family.avatar"
          :title="family.name"
          alt="avatar"
          class="size-6 rounded ring ring-muted/25"
        />

        <div v-if="project.families_count > project.families.length">
          <svg class="size-10 fill-current stroke-accent-foreground/20 text-muted-foreground/50" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="2" />
            <circle cx="19" cy="12" r="2" />
            <circle cx="5" cy="12" r="2" />
          </svg>
        </div>
      </Link>
    </section>

    <section class="my-wide-y border-b border-foreground/10 pb-wide-y">
      <div class="mb-base flex-1 text-right text-xs">{{ _('Members') }} ({{ project.members_count }})</div>

      <Link class="flex flex-wrap gap-4" :href="route('members.index')">
        <img
          v-for="member in project.members"
          :key="member.id"
          :src="member.avatar"
          :title="member.name"
          alt="avatar"
          class="size-6 rounded-full ring ring-muted/25"
        />

        <div v-if="project.members_count > project.members.length">
          <svg class="size-10 fill-current stroke-accent-foreground/20 text-muted-foreground/50" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="2" />
            <circle cx="19" cy="12" r="2" />
            <circle cx="5" cy="12" r="2" />
          </svg>
        </div>
      </Link>
    </section>

    <section class="my-wide-y border-b border-foreground/10 pb-wide-y">
      <div class="mb-base flex-1 text-right text-xs">{{ _('Admins') }} ({{ project.admins_count }})</div>

      <Link class="flex flex-wrap gap-4" :href="route('admins.index')">
        <img
          v-for="admin in project.admins"
          :key="admin.id"
          :src="admin.avatar"
          :title="admin.name"
          alt="avatar"
          class="size-6 rounded ring ring-muted/25"
        />

        <div v-if="project.admins_count > project.admins.length">
          <svg class="size-10 fill-current stroke-accent-foreground/20 text-muted-foreground/40" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="2" />
            <circle cx="19" cy="12" r="2" />
            <circle cx="5" cy="12" r="2" />
          </svg>
        </div>
      </Link>
    </section>

    <section class="mt-base text-center">
      <SelectDeselect :project="project" :selected="currentProject?.id === project.id" />
    </section>
  </Card>
</template>
