<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import { Filters, Search, Switch, Options, SEARCH, SWITCH, OPTIONS } from '@/components/filtering';
import type { OptionValue } from '@/components/filtering/types';
import { Moon, Sun } from 'lucide-vue-next';

const theme = ref<'light' | 'dark'>('dark');

// Filtering demo data
const filterDemoData = ref({
  searchQuery: '',
  viewMode: 'list',
  category: 'all'
});

const viewModeOptions = {
  list: 'List View',
  grid: 'Grid View'
};

const categoryOptions = {
  all: 'All Categories',
  residential: 'Residential',
  commercial: 'Commercial',
  mixed: 'Mixed Use',
  development: 'In Development',
  industrial: 'Industrial',
  retail: 'Retail'
};

// Example config for config-driven usage
const exampleFilterConfig = {
  q: { type: SEARCH },
  scope: {
    type: SWITCH,
    options: { on: 'Enabled', off: 'Disabled' },
  },
  categories: {
    type: OPTIONS,
    options: { web: 'Web Development', mobile: 'Mobile Apps', api: 'API Services' }
  }
};

const configFilterData = ref<Record<string, OptionValue>>({});

const handleConfigUpdate = (data: Record<string, OptionValue>) => {
  configFilterData.value = data;
  console.log('Config filters updated:', data);
};

// Example of navigation function
const navigateWithFilters = (data: Record<string, OptionValue>) => {
  console.log('Navigate with filters:', data);
  // In real app: router.reload({ data });
};

const toggleTheme = () => {
  theme.value = theme.value === 'light' ? 'dark' : 'light';
  document.documentElement.classList.toggle('dark');
};
</script>

<template>
  <Head title="Filtering Components Demo" />

  <Breadcrumbs global>
    <Breadcrumb route="dev.dashboard">Dev</Breadcrumb>
    <Breadcrumb route="dev.filters" no-link>Filtering Components</Breadcrumb>
  </Breadcrumbs>

  <div class="min-h-screen bg-surface p-8">
    <div class="mx-auto max-w-5xl">
      <!-- Header -->
      <div class="mb-8 flex flex-col gap-4 @md:flex-row @md:items-center @md:justify-between">
        <div class="min-w-0 flex-1">
          <h1 class="text-4xl font-bold text-text">Filtering Components</h1>
          <p class="mt-2 text-text-muted">
            Interactive filtering components with sticky header behavior
          </p>
        </div>
        <div class="flex-shrink-0">
          <button @click="toggleTheme"
            class="flex min-h-[44px] items-center gap-2 rounded-lg bg-interactive px-4 py-2 text-interactive-foreground transition-all hover:bg-interactive-hover focus:outline-0 focus:ring-2 focus:ring-focus-ring focus:ring-offset-2 focus:ring-offset-focus-ring-offset @md:min-h-[36px]">
            <Sun v-if="theme === 'dark'" class="h-5 w-5" />
            <Moon v-else class="h-5 w-5" />
            <span>{{ theme === 'light' ? 'Light' : 'Dark' }} Theme</span>
          </button>
        </div>
      </div>

      <!-- Demo Section -->
      <div class="space-y-8">
        <!-- Overview -->
        <div class="rounded-lg border border-border bg-surface-elevated p-6">
          <h2 class="mb-4 text-xl font-semibold text-text">Overview</h2>
          <div class="space-y-4 text-text-muted">
            <p>
              These components teleport to the header area (using the <code class="px-1.5 py-0.5 rounded bg-surface-sunken text-text font-mono text-sm">data-slot='header-after'</code> teleport target)
              and provide sticky filtering capabilities with a semi-transparent backdrop.
            </p>
            <ul class="list-disc space-y-2 pl-6">
              <li><strong>Search:</strong> Text input with debounced search functionality</li>
              <li><strong>Switch:</strong> Two-option toggle for binary choices (List/Grid, Active/Inactive, etc.)</li>
              <li><strong>Options:</strong> Dropdown for multiple options (Categories, Status filters, etc.)</li>
            </ul>
          </div>
        </div>

        <!-- API Patterns -->
        <div class="rounded-lg border border-border bg-surface-elevated p-6">
          <h2 class="mb-4 text-xl font-semibold text-text">API Patterns</h2>
          <div class="space-y-6 text-text-muted">
            <div>
              <h3 class="mb-2 text-lg font-medium text-text">1. Standalone Components</h3>
              <p class="mb-3">Simple components that emit <code class="px-1.5 py-0.5 rounded bg-surface-sunken text-text font-mono text-sm">@input</code> events:</p>
              <div class="text-xs text-text-subtle font-mono bg-surface-sunken rounded p-3">
                &lt;Search @input="handleSearch" /><br/>
                &lt;Switch :options="{on: 'On', off: 'Off'}" @input="handleSwitch" /><br/>
                &lt;Options :options="categoryOptions" @input="handleCategory" />
              </div>
            </div>

            <div>
              <h3 class="mb-2 text-lg font-medium text-text">2. Config-Driven Filters</h3>
              <p class="mb-3">Auto-generate all filters from configuration object:</p>
              <div class="text-xs text-text-subtle font-mono bg-surface-sunken rounded p-3">
                &lt;Filters :config="filterConfig" :filter="navigateWithFilters" />
              </div>
            </div>

            <div>
              <h3 class="mb-2 text-lg font-medium text-text">3. Manual Wrapper</h3>
              <p class="mb-3">Use Filters as wrapper for sticky header behavior:</p>
              <div class="text-xs text-text-subtle font-mono bg-surface-sunken rounded p-3">
                &lt;Filters><br/>
                &nbsp;&nbsp;&lt;Search @input="..." /><br/>
                &nbsp;&nbsp;&lt;Switch :options="..." @input="..." /><br/>
                &lt;/Filters>
              </div>
            </div>
          </div>
            <p class="text-sm bg-info-subtle border border-info rounded-lg p-4 text-info-subtle-foreground">
              <strong>Try scrolling:</strong> The filter components above will stick to the top of the page as you scroll down.
            </p>
          </div>
        </div>

        <!-- Live State Display -->
        <div class="rounded-lg border border-border bg-surface-elevated p-6">
          <h3 class="mb-4 text-lg font-semibold text-text">Live State</h3>
          <p class="mb-4 text-sm text-text-muted">
            This shows the current state of the filtering components. Try interacting with the filters in the header to see live updates:
          </p>
          <pre class="rounded bg-surface-sunken p-4 text-sm text-text-muted overflow-x-auto">{{ JSON.stringify(filterDemoData, null, 2) }}</pre>
        </div>

        <!-- Component Documentation -->
        <div class="grid gap-6 @lg:grid-cols-3">
          <!-- Filters Component -->
          <div class="rounded-lg border border-border bg-surface-elevated p-6">
            <h3 class="mb-3 text-lg font-semibold text-text">Filters</h3>
            <p class="mb-4 text-sm text-text-muted">
              Base wrapper component that teleports content to the header and provides the sticky backdrop.
            </p>
            <div class="text-xs text-text-subtle font-mono bg-surface-sunken rounded p-3">
              &lt;Filters&gt;<br/>
              &nbsp;&nbsp;/* content */<br/>
              &lt;/Filters&gt;
            </div>
          </div>

          <!-- Search Component -->
          <div class="rounded-lg border border-border bg-surface-elevated p-6">
            <h3 class="mb-3 text-lg font-semibold text-text">Search</h3>
            <p class="mb-4 text-sm text-text-muted">
              Debounced search input that matches the height of other filter components.
            </p>
            <div class="text-xs text-text-subtle font-mono bg-surface-sunken rounded p-3">
              &lt;Search<br/>
              &nbsp;&nbsp;placeholder="Search..."<br/>
              &nbsp;&nbsp;class="flex-1"<br/>
              /&gt;
            </div>
          </div>

          <!-- Switch Component -->
          <div class="rounded-lg border border-border bg-surface-elevated p-6">
            <h3 class="mb-3 text-lg font-semibold text-text">Switch</h3>
            <p class="mb-4 text-sm text-text-muted">
              Two-option toggle with equal-width columns. Perfect for binary choices.
            </p>
            <div class="text-xs text-text-subtle font-mono bg-surface-sunken rounded p-3">
              &lt;Switch<br/>
              &nbsp;&nbsp;:options="options"<br/>
              &nbsp;&nbsp;:active="value"<br/>
              &nbsp;&nbsp;@change="handler"<br/>
              /&gt;
            </div>
          </div>
        </div>

        <!-- Options Documentation -->
        <div class="rounded-lg border border-border bg-surface-elevated p-6">
          <h3 class="mb-3 text-lg font-semibold text-text">Options</h3>
          <p class="mb-4 text-sm text-text-muted">
            Dropdown component for multiple options. Scales well from 3 to 10+ options with smooth animations and outside-click handling.
          </p>
          <div class="text-xs text-text-subtle font-mono bg-surface-sunken rounded p-3">
            &lt;Options<br/>
            &nbsp;&nbsp;:options="categoryOptions"<br/>
            &nbsp;&nbsp;:active="selectedCategory"<br/>
            &nbsp;&nbsp;@change="updateCategory"<br/>
            &nbsp;&nbsp;placeholder="Select category..."<br/>
            &nbsp;&nbsp;class="min-w-32"<br/>
            /&gt;
          </div>
        </div>

        <!-- Usage Notes -->
        <div class="rounded-lg border border-border bg-surface-elevated p-6">
          <h3 class="mb-4 text-lg font-semibold text-text">Usage Notes</h3>
          <div class="space-y-4 text-sm text-text-muted">
            <div>
              <h4 class="font-semibold text-text mb-2">Responsive Behavior:</h4>
              <ul class="list-disc space-y-1 pl-5">
                <li>On large screens: Search takes remaining space, filters align right</li>
                <li>On small screens: Filters center-align when wrapping to new lines</li>
                <li>All components maintain consistent 48px height</li>
              </ul>
            </div>
            <div>
              <h4 class="font-semibold text-text mb-2">Design Features:</h4>
              <ul class="list-disc space-y-1 pl-5">
                <li>Semi-transparent background with backdrop blur</li>
                <li>Gradient background from --background to --background</li>
                <li>Equal-width Switch buttons with grid layout</li>
                <li>Smooth dropdown animations with outside-click detection</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

  <!-- Live Demo - This teleports to the header -->
  <Filters class="md:pt-0">
    <!-- Search component (standalone) -->
    <Search
      v-model="filterDemoData.searchQuery"
      @input="filterDemoData.searchQuery = $event"
      placeholder="Search projects..."
      class="flex-1"
    />

    <!-- Filters on the right -->
    <div class="flex flex-wrap items-center justify-center gap-3 lg:justify-end">
      <!-- View Mode Switch (2 options) -->
      <Switch
        :options="viewModeOptions"
        v-model="filterDemoData.viewMode"
        @input="filterDemoData.viewMode = $event"
      />

      <!-- Category Dropdown (multiple options) -->
      <Options
        :options="categoryOptions"
        v-model="filterDemoData.category"
        @input="filterDemoData.category = $event"
        placeholder="All Categories"
        class="min-w-40"
      />
    </div>
  </Filters>

  <!-- Config-Driven Demo -->
  <Filters
    :config="exampleFilterConfig"
    v-model="configFilterData"
    @input="handleConfigUpdate"
    :filter="navigateWithFilters"
  />
</template>