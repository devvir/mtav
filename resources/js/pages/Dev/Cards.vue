<script setup lang="ts">
import { Badge } from '@/components/badge';
import CardContent from '@/components/card/CardContent.vue';
import CardFooter from '@/components/card/CardFooter.vue';
import CardHeader from '@/components/card/CardHeader.vue';
import EntityCard from '@/components/card/EntityCard.vue';
import { Calendar, Camera, Check, Clock } from 'lucide-vue-next';

// Simple mock data for showcase
const sampleEventResource = {
  id: 1,
  name: 'Annual Charity Gala',
  type: 'fundraiser',
  description: 'Join us for an elegant evening supporting local charities.',
  date: '2024-12-15T19:00:00Z',
  location: 'Grand Ballroom, Downtown Hotel',
  status: 'published',
  // Add minimal required fields for ApiResource
  created_at: 'Nov 1, 2024 10:00 AM',
  created_ago: '1 week ago',
  deleted_at: null,
  allows: { update: true, delete: false },
};

// Mock resource for general demo purposes
const mockResource = {
  id: 2,
  name: 'Sample Resource',
  description: 'A sample resource for demo purposes',
  created_at: 'Nov 1, 2024 10:00 AM',
  created_ago: '1 week ago',
  deleted_at: null,
  allows: { update: true, delete: false },
};
</script>

<template>
  <div class="mx-auto max-w-7xl space-y-8 p-8">
    <header class="mb-12 text-center">
      <h1 class="mb-4 text-3xl font-bold text-text">EntityCard Component System</h1>
      <p class="text-lg text-text-muted">
        Showcasing the flexible EntityCard system with various combinations and styling options
      </p>
    </header>

    <!-- Basic Cards Grid -->
    <section class="space-y-6">
      <h2 class="text-2xl font-semibold text-text">Basic EntityCard Variations</h2>
      <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
        <!-- 1. Long Title with Edit Button -->
        <EntityCard :resource="sampleEventResource" entity="event">
          <CardHeader
            title="This is a very long card title that demonstrates how the grid layout handles text truncation alongside an edit button in the same row"
          />
          <CardContent>
            <p class="text-text-muted">
              This card tests long title truncation with an edit button present, ensuring proper
              grid behavior.
            </p>
          </CardContent>
        </EntityCard>

        <!-- 2. EntityCard with Icon -->
        <EntityCard :resource="mockResource" entity="event">
          <CardHeader title="EntityCard with Icon" :icon="Calendar" />
          <CardContent>
            <p class="text-text-muted">
              This card includes an icon in the header for visual context.
            </p>
          </CardContent>
        </EntityCard>

        <!-- 3. EntityCard with Subtitle -->
        <EntityCard :resource="mockResource" entity="event">
          <CardHeader
            title="Event Planning Meeting"
            subtitle="Monthly team sync for upcoming events"
          />
          <CardContent>
            <p class="text-text-muted">
              Cards can include subtitles for additional context below the main title.
            </p>
          </CardContent>
        </EntityCard>
      </div>
    </section>

    <!-- Advanced Cards Grid -->
    <section class="space-y-6">
      <h2 class="text-2xl font-semibold text-text">Advanced EntityCard Features</h2>
      <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <!-- 4. Full-featured Event EntityCard -->
        <EntityCard :resource="sampleEventResource" entity="event">
          <CardHeader title="Annual Charity Gala" subtitle="Fundraising Event" :icon="Calendar">
            <Badge variant="outline">
              <Check class="mr-1 size-3" />
              Published
            </Badge>
            <Badge variant="secondary">Fundraiser</Badge>
          </CardHeader>
          <CardContent>
            <p class="mb-3 text-text-muted">
              Join us for an elegant evening supporting local charities. Featuring live music,
              silent auction, and gourmet dinner.
            </p>
            <div class="space-y-2 text-sm">
              <div class="flex items-center gap-2 text-text-subtle">
                <Calendar class="size-4" />
                December 15, 2024 at 7:00 PM
              </div>
              <div class="flex items-center gap-2 text-text-subtle">
                <Clock class="size-4" />
                3 hours duration
              </div>
            </div>
          </CardContent>
          <CardFooter>
            <div class="flex w-full items-center justify-between">
              <span>Created 2 days ago</span>
              <span class="text-xs">ID: #001</span>
            </div>
          </CardFooter>
        </EntityCard>

        <!-- 5. Media EntityCard -->
        <EntityCard :resource="mockResource" entity="media">
          <CardHeader
            title="Photography Workshop"
            subtitle="Learn professional techniques"
            :icon="Camera"
          >
            <Badge variant="outline">Draft</Badge>
          </CardHeader>
          <CardContent>
            <div
              class="mb-4 flex h-32 items-center justify-center rounded-lg bg-border text-text-muted"
            >
              <Camera class="size-8" />
            </div>
            <p class="text-sm text-text-muted">
              A hands-on workshop covering composition, lighting, and post-processing techniques.
            </p>
          </CardContent>
          <CardFooter>
            <span class="text-text-subtle">Unpublished • Needs review</span>
          </CardFooter>
        </EntityCard>
      </div>
    </section>

    <!-- Layout & Truncation Tests -->
    <section class="space-y-6">
      <h2 class="text-2xl font-semibold text-text">Layout & Truncation Tests</h2>
      <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
        <!-- 6. Long Title Truncation -->
        <EntityCard :resource="mockResource" entity="event">
          <CardHeader
            title="This is an extremely long event title that should be properly truncated to prevent layout issues and maintain design consistency across all card instances"
            subtitle="Very long subtitle that also needs to be truncated properly to avoid breaking the card layout"
            :icon="Calendar"
          />
          <CardContent>
            <p class="text-text-muted">
              Testing how the card handles very long titles and subtitles.
            </p>
          </CardContent>
        </EntityCard>

        <!-- 7. Rich Content EntityCard -->
        <EntityCard :resource="mockResource" entity="event">
          <CardHeader title="Content Showcase">
            <Badge variant="danger">Urgent</Badge>
            <Badge variant="default">High Priority</Badge>
          </CardHeader>
          <CardContent class="space-y-3">
            <p class="text-text">This card demonstrates rich content capabilities:</p>
            <ul class="ml-4 list-inside list-disc space-y-1 text-sm text-text-muted">
              <li>Multiple content types</li>
              <li>Structured lists</li>
              <li>Various text styles</li>
            </ul>
            <div class="rounded-md bg-muted p-3">
              <code class="text-xs">console.log('Cards support any content');</code>
            </div>
          </CardContent>
          <CardFooter>
            <div class="text-xs text-text-subtle">Last updated: 1 hour ago</div>
          </CardFooter>
        </EntityCard>

        <!-- 8. Custom Styled EntityCard -->
        <EntityCard
          :resource="mockResource"
          entity="event"
          class="border-blue-200 bg-gradient-to-br from-blue-50 to-indigo-50"
        >
          <CardHeader
            title="Custom Styled EntityCard"
            subtitle="With gradient background"
            class="text-blue-900"
          >
            <Badge class="bg-blue-100 text-blue-800 hover:bg-blue-200">Custom</Badge>
          </CardHeader>
          <CardContent>
            <p class="text-blue-700">
              This card shows how the component system supports custom styling while maintaining
              consistent structure and behavior.
            </p>
          </CardContent>
          <CardFooter class="border-blue-200 text-blue-600">
            <span>Styled with custom classes</span>
          </CardFooter>
        </EntityCard>
      </div>
    </section>

    <!-- Usage Guidelines -->
    <section class="mt-12 rounded-lg bg-muted p-6">
      <h2 class="mb-4 text-xl font-semibold text-text">Usage Guidelines</h2>
      <div class="grid grid-cols-1 gap-6 text-sm md:grid-cols-2">
        <div>
          <h3 class="mb-2 font-medium text-text">Component Structure</h3>
          <ul class="space-y-1 text-text-muted">
            <li>• <code>EntityCard</code> - Root container with semantic HTML</li>
            <li>• <code>CardHeader</code> - Icon, title, subtitle, badges</li>
            <li>• <code>CardContent</code> - Main content area</li>
            <li>• <code>CardFooter</code> - Metadata and actions</li>
          </ul>
        </div>
        <div>
          <h3 class="mb-2 font-medium text-text">Key Features</h3>
          <ul class="space-y-1 text-text-muted">
            <li>• Automatic separators between sections</li>
            <li>• Built-in truncation for long content</li>
            <li>• Flexible icon support (components or emoji)</li>
            <li>• Responsive grid layout</li>
            <li>• Mergeable CSS classes via <code>cn()</code></li>
          </ul>
        </div>
      </div>
    </section>
  </div>
</template>
