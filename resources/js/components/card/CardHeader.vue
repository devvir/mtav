<script setup lang="ts">
import { Avatar, type AvatarSize } from '@/components/avatar';
import { Badge } from '@/components/badge';
import { _ } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';
import { ActionsType, CardActions, type CardType, HeaderSub } from '.';
import * as exposed from './exposed';

defineEmits<{
  (e: 'execute', action: ResourceAction): void;
}>(); /** CardActions event */

defineProps<{
  title: string;
  kicker?: string;
  avatar?: AvatarSize;
  class?: any;
}>();

const resource = inject(exposed.resource, {}) as ApiResource;
const cardType = inject(exposed.type, {}) as CardType;
const routes = inject(exposed.routes, {}) as Record<ResourceAction, string>;

const autoActions = { index: 'subtle', show: 'full' };

const actionsType = computed<ActionsType | null>(() => autoActions[cardType] ?? null);
</script>

<template>
  <header :class="cn($props.class, 'relative flex min-w-0 flex-wrap gap-x-base')">
    <slot name="icon">
      <Avatar v-if="avatar" :subject="resource" :size="avatar" />
    </slot>

    <section class="min-w-0 flex-1 space-y-0.5 truncate">
      <h3
        v-if="kicker || $slots.kicker"
        class="text-xs tracking-wide text-text-subtle"
        :class="{ 'uppercase text-text-subtle/60': !$slots.kicker }"
      >
        <slot name="kicker">
          {{ kicker }}
        </slot>
      </h3>

      <h2
        class="truncate text-sm font-semibold tracking-wide text-text @2xs:text-base @xs:text-lg"
        :title="title"
      >
        {{ title }}
      </h2>

      <div class="flex min-w-0 items-center gap-2 text-xs text-text-subtle">
        <div class="flex-1 truncate">
          <slot :resource :card-type="cardType" :entity-routes="routes">
            <HeaderSub v-if="resource.project?.name" :title="resource.project.name">
              {{ resource.project.name }}
            </HeaderSub>
          </slot>
        </div>

        <!-- Soft-Deleted Badge -->
        <Badge
          v-if="resource?.deleted_at"
          variant="destructive"
          class="flex-shrink-0 px-2 py-0.5 text-xs"
        >
          {{ _('Soft-Deleted') }}
        </Badge>
      </div>
    </section>

    <CardActions
      v-if="actionsType"
      :type="actionsType"
      :class="['text-sm', actionsType == 'full' ? 'mt-4 -mb-2! basis-full' : '-mt-2']"
      @execute="$emit('execute', $event)"
    />
  </header>
</template>
