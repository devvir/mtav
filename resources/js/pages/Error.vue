<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps<{ status: number }>();

const content = computed(
  () =>
    ({
      403: { title: _('Not allowed'), message: _('You do not have permission to see this page.') },
      404: {
        title: _('Page not found'),
        message: _('This page does not exist, or you do not have access to it.'),
      },
      503: {
        title: _('Back soon'),
        message: _('We are doing some maintenance. Please try again in a few minutes.'),
      },
    })[props.status] ?? {
      title: _('Something went wrong'),
      message: _('Please try again. If it keeps happening, let us know.'),
    },
);
</script>

<template>
  <Head :title="content.title" />
  <div class="mx-auto max-w-md py-16 text-center">
    <p class="text-6xl font-bold text-text-subtle">{{ status }}</p>
    <h1 class="mt-4 text-2xl font-bold">{{ content.title }}</h1>
    <p class="mt-2 text-text-muted">{{ content.message }}</p>
    <Button class="mt-8" as-child>
      <Link :href="route('home')">{{ _('Go to the start') }}</Link>
    </Button>
  </div>
</template>
