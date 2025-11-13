<script setup lang="ts">
import { cn } from '@/lib/utils';
import {Avatar, type AvatarSize } from '@/components/avatar';
import { Badge } from '@/components/badge';
import { ActionsType, CardActions, type CardType, HeaderSub } from '.';
import * as exposed from './exposed';
import { _ } from '@/composables/useTranslations';

defineProps<{
  title: string;
  kicker?: string;
  avatar?: AvatarSize;
  class?: any;
}>();

const resource = inject(exposed.resource) as ApiResource;
const cardType = inject(exposed.type) as CardType;

const autoActions = { index: 'subtle', show: 'full' };

const actionsType = computed<ActionsType | null>(() => autoActions[cardType] ?? null);
</script>

<template>
  <header :class="cn($props.class, 'flex flex-wrap gap-x-base min-w-0 relative')">
    <Avatar v-if="avatar" :subject="resource" :size="avatar" />

    <section class="min-w-0 flex-1 truncate space-y-0.5">
      <h3 v-if="kicker" v-html="kicker"
          class="text-xs uppercase tracking-wide text-text-subtle" />

      <h2 class="truncate text-lg font-semibold tracking-wide text-text" :title="title">
        {{ title }}
      </h2>

      <div class="text-xs text-text-subtle flex items-center gap-2 min-w-0">
        <div class="truncate flex-1">
          <slot>
            <HeaderSub v-if="resource.project?.name" :title="resource.project.name">
              {{ resource.project.name }}
            </HeaderSub>
          </slot>
        </div>

        <!-- Soft-Deleted Badge -->
        <Badge v-if="resource.deleted_at" variant="destructive" class="text-xs px-2 py-0.5 flex-shrink-0">
          {{ _('Soft-Deleted') }}
        </Badge>
      </div>
    </section>

    <CardActions
      v-if="actionsType"
      :type="actionsType"
      :class="['text-sm', actionsType == 'full' ? 'basis-full mt-4 -mb-4!' : '-mt-2']"
    />
  </header>
</template>