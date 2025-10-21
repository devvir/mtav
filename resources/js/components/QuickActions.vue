<script setup lang="ts">
import { can, iAmAdmin } from '@/composables/useAuth';
import { projectIsSelected } from '@/composables/useProjects';
import { currentRoute } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import { ModalLink } from '@inertiaui/modal-vue';

type QuickAction = {
  route: string;
  text: string;
  disabled?: boolean;
  if?: MaybeRef<boolean>;
};

const quickActions: QuickAction[] = [
  { if: can.create('projects'), route: 'projects.create', text: 'New Project' },
  { if: can.create('families'), route: 'families.create', text: 'New Family' },
  {
    if: can.create('members'),
    route: 'members.create',
    text: iAmAdmin.value ? 'New User' : 'Invite Family Member',
  },
  { if: can.create('admins'), route: 'admins.create', text: 'New Admin' },
  { if: projectIsSelected, route: 'media.create', text: 'Upload Multimedia' }, // TODO : can.create('media') when the Media model is added
  { if: computed(() => projectIsSelected.value && iAmAdmin.value), route: 'events.create', text: 'New Event' }, // TODO : idem, when the Event model is added
];

const availableActions = computed(() =>
  quickActions
    .map((a) => ({ ...a, disabled: a.route === currentRoute.value }))
    .filter((action) => toValue(action.if) !== false),
);

const open = ref(false);
const trigger = useTemplateRef<HTMLElement>('trigger');

onClickOutside(trigger, () => (open.value = false), {
  ignore: ['.disabled-action'],
});
</script>

<template>
  <div class="relative" aria-haspopup="true" :aria-expanded="open" role="button">
    <button
      ref="trigger"
      tabindex="0"
      class="group -mr-2 flex cursor-pointer items-center gap-3 rounded-xs px-2 text-muted-foreground/60 outline-offset-8"
      @click="open = !open"
      @keyup.enter="open = !open"
      @keyup.escape="open = false"
    >
      <div
        class="border-b-2 border-accent-foreground/70 pb-1 text-sm font-semibold tracking-wide transition-all"
        :class="open ? 'cursor-default font-light text-muted-foreground/20' : 'group-hocus:text-muted-foreground/90'"
      >
        {{ _('Quick Actions') }}
      </div>

      <div
        class="my-1 rounded-full text-background transition-all"
        :class="
          open
            ? 'scale-90 rotate-45 bg-foreground/80'
            : 'bg-foreground/90 group-hocus:scale-105 group-hocus:bg-foreground'
        "
      >
        <svg class="size-7 stroke-current" viewBox="0 0 24 24" :aria-label="_('Open Quick Actions Menu')">
          <path d="M6 12H18" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
          <path d="M12 6L12 18" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
      </div>
    </button>

    <ul
      class="absolute top-12 right-base mr-3 flex min-w-42 origin-top flex-col divide-y-1 divide-accent/8 overflow-hidden rounded-xl border border-accent/15 bg-muted text-sm text-primary shadow shadow-accent/70 backdrop-blur-2xl transition-all ease-out select-none"
      :class="{ 'invisible -rotate-x-90 opacity-0': !open }"
      @keyup.escape="open = false"
    >
      <li
        v-for="action in availableActions"
        :key="action.route"
        class="leading-10 text-nowrap"
        :class="action.disabled ? 'disabled-action bg-accent-foreground/70' : 'hocus:bg-accent-foreground/35'"
      >
        <div v-if="action.disabled" class="pointer-events-none size-full px-4">{{ _(action.text) }}</div>

        <ModalLink v-else class="block size-full px-4" :href="route(action.route)" slideover>
          {{ _(action.text) }}
        </ModalLink>
      </li>
    </ul>
  </div>
</template>
