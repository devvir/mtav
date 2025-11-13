<script setup lang="ts">
import { Calendar, Check, Clock, Camera } from 'lucide-vue-next';
import { Badge } from '@/components/badge';
import Card from '@/components/card/Card.vue';
import CardHeader from '@/components/card/CardHeader.vue';
import CardContent from '@/components/card/CardContent.vue';
import CardFooter from '@/components/card/CardFooter.vue';

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
    allows: { update: true, delete: false }
};

// Mock resource for general demo purposes
const mockResource = {
    id: 2,
    name: 'Sample Resource',
    description: 'A sample resource for demo purposes',
    created_at: 'Nov 1, 2024 10:00 AM',
    created_ago: '1 week ago',
    deleted_at: null,
    allows: { update: true, delete: false }
};
</script>

<template>
    <div class="space-y-8 max-w-7xl mx-auto p-8">
        <header class="text-center mb-12">
            <h1 class="text-3xl font-bold text-text mb-4">Card Component System</h1>
            <p class="text-text-muted text-lg">
                Showcasing the flexible Card system with various combinations and styling options
            </p>
        </header>

        <!-- Basic Cards Grid -->
        <section class="space-y-6">
            <h2 class="text-2xl font-semibold text-text">Basic Card Variations</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                <!-- 1. Long Title with Edit Button -->
                <Card
                  :resource="sampleEventResource"
                  entity="event"
                >
                    <CardHeader
                        title="This is a very long card title that demonstrates how the grid layout handles text truncation alongside an edit button in the same row"
                    />
                    <CardContent>
                        <p class="text-text-muted">This card tests long title truncation with an edit button present, ensuring proper grid behavior.</p>
                    </CardContent>
                </Card>

                <!-- 2. Card with Icon -->
                <Card :resource="mockResource" entity="event">
                    <CardHeader title="Card with Icon" :icon="Calendar" />
                    <CardContent>
                        <p class="text-text-muted">This card includes an icon in the header for visual context.</p>
                    </CardContent>
                </Card>

                <!-- 3. Card with Subtitle -->
                <Card :resource="mockResource" entity="event">
                    <CardHeader
                        title="Event Planning Meeting"
                        subtitle="Monthly team sync for upcoming events"
                    />
                    <CardContent>
                        <p class="text-text-muted">Cards can include subtitles for additional context below the main title.</p>
                    </CardContent>
                </Card>

            </div>
        </section>

        <!-- Advanced Cards Grid -->
        <section class="space-y-6">
            <h2 class="text-2xl font-semibold text-text">Advanced Card Features</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- 4. Full-featured Event Card -->
                <Card
                  :resource="sampleEventResource"
                  entity="event"
                >
                    <CardHeader
                        title="Annual Charity Gala"
                        subtitle="Fundraising Event"
                        :icon="Calendar"
                    >
                        <Badge variant="outline">
                            <Check class="size-3 mr-1" />
                            Published
                        </Badge>
                        <Badge variant="secondary">Fundraiser</Badge>
                    </CardHeader>
                    <CardContent>
                        <p class="text-text-muted mb-3">
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
                        <div class="flex justify-between items-center w-full">
                            <span>Created 2 days ago</span>
                            <span class="text-xs">ID: #001</span>
                        </div>
                    </CardFooter>
                </Card>

                <!-- 5. Media Card -->
                <Card :resource="mockResource" entity="media">
                    <CardHeader
                        title="Photography Workshop"
                        subtitle="Learn professional techniques"
                        :icon="Camera"
                    >
                        <Badge variant="outline">Draft</Badge>
                    </CardHeader>
                    <CardContent>
                        <div class="bg-border rounded-lg h-32 mb-4 flex items-center justify-center text-text-muted">
                            <Camera class="size-8" />
                        </div>
                        <p class="text-text-muted text-sm">
                            A hands-on workshop covering composition, lighting, and post-processing techniques.
                        </p>
                    </CardContent>
                    <CardFooter>
                        <span class="text-text-subtle">Unpublished • Needs review</span>
                    </CardFooter>
                </Card>

            </div>
        </section>

        <!-- Layout & Truncation Tests -->
        <section class="space-y-6">
            <h2 class="text-2xl font-semibold text-text">Layout & Truncation Tests</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                <!-- 6. Long Title Truncation -->
                <Card :resource="mockResource" entity="event">
                    <CardHeader
                        title="This is an extremely long event title that should be properly truncated to prevent layout issues and maintain design consistency across all card instances"
                        subtitle="Very long subtitle that also needs to be truncated properly to avoid breaking the card layout"
                        :icon="Calendar"
                    />
                    <CardContent>
                        <p class="text-text-muted">Testing how the card handles very long titles and subtitles.</p>
                    </CardContent>
                </Card>

                <!-- 7. Rich Content Card -->
                <Card :resource="mockResource" entity="event">
                    <CardHeader title="Content Showcase">
                        <Badge variant="danger">Urgent</Badge>
                        <Badge variant="default">High Priority</Badge>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <p class="text-text">This card demonstrates rich content capabilities:</p>
                        <ul class="list-disc list-inside space-y-1 text-sm text-text-muted ml-4">
                            <li>Multiple content types</li>
                            <li>Structured lists</li>
                            <li>Various text styles</li>
                        </ul>
                        <div class="bg-muted p-3 rounded-md">
                            <code class="text-xs">console.log('Cards support any content');</code>
                        </div>
                    </CardContent>
                    <CardFooter>
                        <div class="text-xs text-text-subtle">
                            Last updated: 1 hour ago
                        </div>
                    </CardFooter>
                </Card>

                <!-- 8. Custom Styled Card -->
                <Card :resource="mockResource" entity="event" class="bg-gradient-to-br from-blue-50 to-indigo-50 border-blue-200">
                    <CardHeader
                        title="Custom Styled Card"
                        subtitle="With gradient background"
                        class="text-blue-900"
                    >
                        <Badge class="bg-blue-100 text-blue-800 hover:bg-blue-200">Custom</Badge>
                    </CardHeader>
                    <CardContent>
                        <p class="text-blue-700">
                            This card shows how the component system supports custom styling
                            while maintaining consistent structure and behavior.
                        </p>
                    </CardContent>
                    <CardFooter class="text-blue-600 border-blue-200">
                        <span>Styled with custom classes</span>
                    </CardFooter>
                </Card>

            </div>
        </section>

        <!-- Usage Guidelines -->
        <section class="bg-muted rounded-lg p-6 mt-12">
            <h2 class="text-xl font-semibold text-text mb-4">Usage Guidelines</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                <div>
                    <h3 class="font-medium text-text mb-2">Component Structure</h3>
                    <ul class="space-y-1 text-text-muted">
                        <li>• <code>Card</code> - Root container with semantic HTML</li>
                        <li>• <code>CardHeader</code> - Icon, title, subtitle, badges</li>
                        <li>• <code>CardContent</code> - Main content area</li>
                        <li>• <code>CardFooter</code> - Metadata and actions</li>
                    </ul>
                </div>
                <div>
                    <h3 class="font-medium text-text mb-2">Key Features</h3>
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