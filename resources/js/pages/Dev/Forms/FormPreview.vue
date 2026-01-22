<script setup lang="ts">
// Copilot - Pending review

import { Form } from '@/components/forms';
import { ChevronDown, ChevronRight } from 'lucide-vue-next';

const props = defineProps<{
  entityKey: string;
  formSpecs: Record<string, { create: any; update: any }>;
}>();

// Get the FormService specs for this entity
const createFormSpec = computed(() => props.formSpecs[props.entityKey]?.create);
const updateFormSpec = computed(() => props.formSpecs[props.entityKey]?.update);

// JSON collapse state
const isCreateJsonExpanded = ref(false);
const isUpdateJsonExpanded = ref(false);
</script>

<template>
  <div class="grid grid-cols-2 gap-8">
    <!-- Create Form -->
    <div class="rounded-lg border border-border bg-background p-6">
      <h3 class="mb-4 text-lg font-semibold text-foreground">Create Form</h3>

      <!-- FormService JSON (Collapsible) -->
      <div class="mb-4 rounded bg-slate-900 p-3">
        <button
          @click="isCreateJsonExpanded = !isCreateJsonExpanded"
          class="flex w-full items-center justify-between text-xs font-semibold text-slate-400 transition-colors hover:text-slate-200"
        >
          <span>FormService Output:</span>
          <ChevronDown v-if="isCreateJsonExpanded" class="h-4 w-4" />
          <ChevronRight v-else class="h-4 w-4" />
        </button>
        <pre v-if="isCreateJsonExpanded" class="mt-2 overflow-x-auto text-xs text-green-400">{{
          JSON.stringify(createFormSpec, null, 2)
        }}</pre>
      </div>

      <!-- FormService-based Form -->
      <div v-if="createFormSpec?.specs" class="rounded-lg border border-border bg-background p-4">
        <Form
          type="create"
          :action="createFormSpec.action?.route"
          :params="createFormSpec.action?.params"
          :title="createFormSpec.title"
          :specs="createFormSpec.specs"
          buttonText="Submit"
          autocomplete="off"
        />
      </div>
      <div v-else class="text-sm text-muted-foreground">No form spec available</div>
    </div>

    <!-- Edit Form -->
    <div class="rounded-lg border border-border bg-background p-6">
      <h3 class="mb-4 text-lg font-semibold text-foreground">Edit Form</h3>

      <!-- FormService JSON (Collapsible) -->
      <div v-if="updateFormSpec" class="mb-4 rounded bg-slate-900 p-3">
        <button
          @click="isUpdateJsonExpanded = !isUpdateJsonExpanded"
          class="flex w-full items-center justify-between text-xs font-semibold text-slate-400 transition-colors hover:text-slate-200"
        >
          <span>FormService Output:</span>
          <ChevronDown v-if="isUpdateJsonExpanded" class="h-4 w-4" />
          <ChevronRight v-else class="h-4 w-4" />
        </button>
        <pre v-if="isUpdateJsonExpanded" class="mt-2 overflow-x-auto text-xs text-green-400">{{
          JSON.stringify(updateFormSpec, null, 2)
        }}</pre>
      </div>

      <!-- FormService-based Form -->
      <div v-if="updateFormSpec?.specs" class="rounded-lg border border-border bg-background p-4">
        <Form
          type="update"
          :action="updateFormSpec.action?.route"
          :params="updateFormSpec.action?.params"
          :title="updateFormSpec.title"
          :specs="updateFormSpec.specs"
          buttonText="Update"
          autocomplete="off"
        />
      </div>
      <p v-else class="text-sm text-muted-foreground">No sample data available</p>
    </div>
  </div>
</template>
