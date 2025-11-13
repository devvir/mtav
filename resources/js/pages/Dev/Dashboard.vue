<script setup lang="ts">
import Head from '@/components/Head.vue';
import { Card, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Sparkles, Palette, MessageSquare, Code, CreditCard } from 'lucide-vue-next';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';

const devPages = [
  {
    title: 'Flash Messages Tester',
    description: 'Test all flash message types (success, info, warning, error) with custom messages',
    icon: MessageSquare,
    route: 'dev.flash',
    color: 'text-blue-500',
  },
  {
    title: 'UI Components',
    description: 'Browse all UI components with live examples and code snippets',
    icon: Palette,
    route: 'dev.ui',
    color: 'text-purple-500',
  },
  {
    title: 'Card Components',
    description: 'Showcase of the new generic Card component system with sample variations',
    icon: CreditCard,
    route: 'dev.cards',
    color: 'text-indigo-500',
  },
  {
    title: 'Entity Cards',
    description: 'Preview of Card component used for all application entities (Projects, Members, etc.)',
    icon: CreditCard,
    route: 'dev.entity-cards',
    color: 'text-rose-500',
  },
  {
    title: 'Playground',
    description: 'Experimental sandbox for testing new features and components',
    icon: Sparkles,
    route: 'dev.playground',
    color: 'text-green-500',
  },
];
</script>

<template>
  <Head title="Dev Dashboard" />

  <Breadcrumbs global>
    <Breadcrumb route="dev.dashboard" no-link>Dev</Breadcrumb>
  </Breadcrumbs>

  <div class="container mx-auto max-w-6xl py-8">
    <div class="mb-8">
      <div class="flex items-center gap-3 mb-2">
        <Code class="h-8 w-8 text-primary" />
        <h1 class="text-3xl font-bold">Developer Dashboard</h1>
      </div>
      <p class="text-text-muted">
        Development tools and testing utilities (only available in local/testing environments)
      </p>
    </div>

    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
      <Card
        v-for="page in devPages"
        :key="page.route"
        class="hover:shadow-lg transition-shadow cursor-pointer"
        @click="router.visit(route(page.route))"
      >
        <CardHeader>
          <div class="flex items-start justify-between">
            <component :is="page.icon" :class="['h-10 w-10 mb-2', page.color]" />
            <Button variant="ghost" size="sm" as-child>
              <Link :href="route(page.route)">
                Open →
              </Link>
            </Button>
          </div>
          <CardTitle>{{ page.title }}</CardTitle>
          <CardDescription>{{ page.description }}</CardDescription>
        </CardHeader>
      </Card>
    </div>

    <div class="mt-12 p-6 bg-muted/50 rounded-lg">
      <h2 class="text-lg font-semibold mb-2">Development Environment</h2>
      <div class="text-sm text-text-muted space-y-1">
        <p><strong>Environment:</strong> {{ $page.props.app?.env || 'local' }}</p>
        <p><strong>Debug Mode:</strong> {{ $page.props.app?.debug ? 'Enabled' : 'Disabled' }}</p>
        <p class="text-xs pt-2 text-amber-600 dark:text-amber-400 font-medium">
          ⚠️ This dashboard is not accessible in production
        </p>
      </div>
    </div>
  </div>
</template>
