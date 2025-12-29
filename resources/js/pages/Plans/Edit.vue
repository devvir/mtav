<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import EditorCanvas from '@/components/projectplan/editor/EditorCanvas.vue';
import EditorSidebar from '@/components/projectplan/editor/EditorSidebar.vue';
import { Button } from '@/components/ui/button';
import { _ } from '@/composables/useTranslations';
import { useHistory } from '@/components/projectplan/composables/useHistory';

const props = defineProps<{
  plan: ApiResource<Plan>;
  project: ApiResource<Project>;
}>();

/**
 * Check if running on desktop (viewport >= 1024px AND mouse pointer available)
 */
const width = window.innerWidth >= 1024;
const hasMouse = window.matchMedia('(pointer:fine)').matches;
const isDesktop = width && hasMouse;

// History management
const { currentState, canUndo, canRedo, saveState, undo, redo, reset } = useHistory({
  items: props.plan.items,
});

const hasChanges = computed(() => {
  return JSON.stringify(currentState.value.items) !== JSON.stringify(props.plan.items);
});

const processing = ref(false);
const saveError = ref(false);

// Handle item moved from canvas
const handleItemMoved = (itemId: number, newPolygon: Point[]) => {
  const newItems = currentState.value.items.map((item: PlanItem) =>
    item.id === itemId ? { ...item, polygon: newPolygon } : item
  );
  saveState({ items: newItems });
  saveError.value = false; // Clear error on any change
};

// Handle save
const saveChanges = () => {
  const payload = {
    polygon: props.plan.polygon,
    items: currentState.value.items.map((item: PlanItem) => ({
      id: item.id,
      polygon: item.polygon,
    })),
  };

  processing.value = true;

  router.patch(route('plans.update', props.plan.id), payload, {
    onFinish: () => processing.value = false,
    onSuccess: () => saveError.value = false, // Clear error on success
    onError: () => saveError.value = true,    // Show error on failure

    // Force full remount (history/state reset) on success
    preserveState: (page) => Object.keys(page.props.errors || {}).length > 0,
  });
};
</script>

<template>
  <Head :title="`${_('Project Plan View')} - ${_('Edit')}`" />

  <Breadcrumbs>
    <Breadcrumb text="Plan" route="plans.edit" :params="{ plan: plan.id }" />
  </Breadcrumbs>

  <!-- Desktop: Editor -->
  <div v-if="isDesktop" class="flex h-full">
    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col overflow-hidden max-w-5xl mx-auto">
      <!-- Header -->
      <div class="border-b border-border bg-card px-6 py-4">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-xl font-bold text-foreground">
              {{ `${_('Project Plan')} "${plan.project.name}"` }}
            </h1>
          </div>

          <div class="flex gap-2">
            <Button
              @click="saveChanges"
              :disabled="!hasChanges || processing"
              class="overflow-hidden grid"
            >
              <div class="row-start-1 col-start-1 transition-opacity" :class="{ 'invisible': !processing }">{{ _('Saving...') }}</div>
              <div class="row-start-1 col-start-1 transition-opacity" :class="{ 'invisible': processing || !hasChanges }">{{ _('Save Changes') }}</div>
              <div class="row-start-1 col-start-1 transition-opacity" :class="{ 'invisible': processing || hasChanges }">{{ _('No Changes') }}</div>
            </Button>
          </div>
        </div>

        <!-- Error message -->
        <div v-if="saveError" class="w-full mt-4 text-right text-sm text-destructive-foreground">
          <span class="py-2 px-3 rounded-lg bg-destructive">{{ _('An error occurred while saving. Please try again.') }}</span>
        </div>
      </div>

      <!-- Canvas -->
      <EditorCanvas
        :plan="plan"
        :items="currentState.items"
        class="mt-4"
        @item-moved="handleItemMoved"
      />
    </div>

    <!-- Sidebar on the right -->
    <EditorSidebar
      :can-undo="canUndo"
      :can-redo="canRedo"
      :has-changes="hasChanges"
      :processing="processing"
      @undo="undo"
      @redo="redo"
      @reset="reset"
    />
  </div>

  <!-- Mobile/Tablet: Notice -->
  <div v-else class="flex items-center justify-center h-96 bg-background">
    <div class="text-center">
      <svg class="mx-auto h-12 w-12 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
      </svg>

      <p class="mt-4 text-lg font-semibold text-foreground">{{ _('Desktop required') }}</p>
      <p class="mt-2 text-muted-foreground">{{ _('Plan editing is only available on desktop with a mouse') }}</p>

      <Link :href="route('lottery')"
        class="mt-6 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-background bg-primary hover:bg-primary/90"
      >{{ _('Back') }}</Link>
    </div>
  </div>
</template>
