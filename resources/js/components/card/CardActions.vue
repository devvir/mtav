<script setup lang="ts">
import Dropdown from '@/components/dropdown/Dropdown.vue';
import DropdownContent from '@/components/dropdown/DropdownContent.vue';
import DropdownTrigger from '@/components/dropdown/DropdownTrigger.vue';
import { Button } from '@/components/ui/button';
import { can } from '@/composables/useAuth';
import { actions, entityNS } from '@/composables/useResources';
import { ChevronDown } from 'lucide-vue-next';
import { ActionsType, CardType } from '.';
import * as buttons from './actions';
import * as exposed from './exposed';

defineProps<{
  type: ActionsType;
}>();

const resource = inject(exposed.resource) as ApiResource;
const entity = inject(exposed.entity) as AppEntity;
const cardType = inject(exposed.type) as CardType;

const enabledActions = computed(() =>
  actions.filter((action) => {
    switch (action) {
      case 'index':
        return cardType != 'index' && can.viewAny(entityNS(entity));
      case 'show':
        return cardType != 'show' && resource.allows.view;
      case 'edit':
        return resource.allows.update;
      case 'destroy':
        return resource.allows.delete && !resource.deleted_at;
      case 'restore':
        return resource.allows.restore && resource.deleted_at;
    }
  }),
);

type CardAction = Exclude<ResourceAction, 'create'>;

const action2component: Record<CardAction, Component> = {
  index: buttons.IndexAction,
  show: buttons.ShowAction,
  edit: buttons.EditAction,
  destroy: buttons.DeleteAction,
  restore: buttons.RestoreAction,
};

const actionComponent: Component = (action: CardAction) => action2component[action];
</script>

<template>
  <!-- Chevron - Simple chevron menu -->
  <div v-if="type === 'subtle'" class="flex items-start justify-end" @click.stop>
    <Dropdown v-slot:default="{ close }">
      <DropdownTrigger tabindex="-1">
        <Button variant="ghost" size="sm" class="mouse-events-none h-7 px-0! text-text-subtle hover:text-text">
          <ChevronDown class="size-6" />
        </Button>
      </DropdownTrigger>

      <DropdownContent
        class="right-0 mt-1 w-32 overflow-hidden rounded-md border border-border bg-popover/85 shadow-lg backdrop-blur-lg"
        @click="close"
      >
        <component
          :is="actionComponent(action)"
          v-for="action in enabledActions"
          :key="action"
          class="w-full justify-start py-5 hocus:bg-surface-interactive-hover"
        />
      </DropdownContent>
    </Dropdown>
  </div>

  <!-- Buttons - Row of labeled buttons -->
  <div v-else class="flex items-center justify-end space-x-1" @click.stop>
    <component
      :is="actionComponent(action)"
      v-for="action in enabledActions"
      :key="action"
      class="border border-border/30 text-xs first:rounded-l-md last:rounded-r-md"
    />
  </div>
</template>
