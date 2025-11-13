<script setup lang="ts">
import { Button } from '@/components/ui/button';
import Dropdown from '@/components/dropdown/Dropdown.vue';
import DropdownContent from '@/components/dropdown/DropdownContent.vue';
import DropdownTrigger from '@/components/dropdown/DropdownTrigger.vue';
import { ChevronDown } from 'lucide-vue-next';
import * as exposed from './exposed';
import * as buttons from './actions';
import { ActionsType, CardType } from '.';
import { can } from '@/composables/useAuth';
import { actions, entityNS } from '@/composables/useResources';

defineProps<{
  type: ActionsType;
}>();

const resource = inject(exposed.resource) as ApiResource;
const entity = inject(exposed.entity) as AppEntity;
const cardType = inject(exposed.type) as CardType;

const enabledActions = computed(() => actions.filter(action => {
    switch (action) {
        case 'index': return cardType != 'index' && can.viewAny(entityNS(entity));
        case 'show': return cardType != 'show' && resource.allows.view;
        case 'edit': return resource.allows.update;
        case 'destroy': return resource.allows.delete && ! resource.deleted_at;
        case 'restore': return resource.allows.restore && resource.deleted_at;
    };
}));

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
          <Button variant="ghost" size="sm"
            class="text-text-subtle hover:text-text h-7 mouse-events-none">
            <ChevronDown class="size-6" />
          </Button>
        </DropdownTrigger>

        <DropdownContent class="right-0 mt-1 w-32 bg-popover/85 backdrop-blur-lg border border-border rounded-md shadow-lg overflow-hidden" @click="close">
          <component :is="actionComponent(action)"
            v-for="action in enabledActions"
            :key="action"
            class="w-full justify-start hocus:bg-surface-interactive-hover py-5"
          />
        </DropdownContent>
      </Dropdown>
    </div>

    <!-- Buttons - Row of labeled buttons -->
    <div v-else class="flex justify-end items-center space-x-1" @click.stop>
      <component :is="actionComponent(action)"
        v-for="action in enabledActions"
        :key="action"
        class="border border-border/30 first:rounded-l-md last:rounded-r-md text-xs"
      />
    </div>
</template>