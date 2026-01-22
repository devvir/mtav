<script setup lang="ts">
// Copilot - Pending review

import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import { Button } from '@/components/ui/button';
import type { PageProps } from '@inertiajs/core';
import { FileText } from 'lucide-vue-next';
import FormPreview from './Forms/FormPreview.vue';
import FormSection from './Forms/FormSection.vue';

const props = defineProps<
  PageProps & {
    formSpecs: Record<string, { create: any; update: any }>;
  }
>();

const entityKeys = Object.keys(props.formSpecs);
const expandedSections = ref<Set<string>>(new Set());

const isExpanded = (key: string) => expandedSections.value.has(key);
const toggleSection = (key: string) => {
  if (expandedSections.value.has(key)) {
    expandedSections.value.delete(key);
  } else {
    expandedSections.value.add(key);
  }
};
const expandAll = () => {
  expandedSections.value = new Set(entityKeys);
};
const collapseAll = () => {
  expandedSections.value = new Set();
};
</script>

<template>
  <div>
    <Head title="Forms" />

    <main class="mx-auto max-w-7xl space-y-6 py-8">
      <header>
        <Breadcrumbs>
          <Breadcrumb route="dev.dashboard" text="Dev" />
          <Breadcrumb route="dev.forms" text="Forms" no-link />
        </Breadcrumbs>
      </header>

      <div class="px-4">
        <!-- Page Header -->
        <div class="mb-6 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <FileText class="h-8 w-8 text-blue-500" />
            <h1 class="text-3xl font-bold text-foreground">Forms Preview</h1>
          </div>

          <div class="flex items-center gap-2">
            <Button @click="expandAll" variant="outline" size="sm"> Expand All </Button>
            <Button @click="collapseAll" variant="outline" size="sm"> Collapse All </Button>
          </div>
        </div>

        <p class="mb-8 text-gray-600">
          Interactive preview and testing interface for all entity forms (Create and Edit).
        </p>

        <!-- Entity Form Sections -->
        <div class="space-y-6">
          <FormSection
            v-for="entityKey in entityKeys"
            :key="entityKey"
            :entity-key="entityKey"
            :is-expanded="isExpanded(entityKey)"
            @toggle="toggleSection(entityKey)"
          >
            <FormPreview :entity-key="entityKey" :form-specs="props.formSpecs" />
          </FormSection>
        </div>
      </div>
    </main>
  </div>
</template>
