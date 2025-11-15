<script setup lang="ts">
import Head from '@/components/Head.vue';
import { _ } from '@/composables/useTranslations';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
  event: ApiResource<Event>;
  types: EventTypes;
}>();

const form = useForm({
  title: props.event.title,
  description: props.event.description || '',
  start_date: props.event.start_date,
  end_date: props.event.end_date,
  type: props.event.type,
  location: props.event.location || '',
  is_published: props.event.is_published,
});

const submit = () => {
  form.put(route('events.update', props.event.id));
};
</script>

<template>
  <div class="space-y-6">
    <Head title="Edit Event" />

    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Event</h1>
      <div class="flex items-center space-x-3">
        <Link
          :href="route('events.show', event.id)"
          class="text-gray-600 hover:text-gray-500 dark:text-gray-400 dark:hover:text-gray-300"
        >
          View Event
        </Link>
        <Link
          :href="route('events.index')"
          class="text-gray-600 hover:text-gray-500 dark:text-gray-400 dark:hover:text-gray-300"
        >
          Back to Events
        </Link>
      </div>
    </div>

    <div
      class="rounded-lg border border-gray-200 bg-white p-6 shadow dark:border-gray-700 dark:bg-gray-800"
    >
      <form @submit.prevent="submit" class="space-y-6">
        <!-- Title -->
        <div>
          <label
            for="title"
            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            Event Title
          </label>
          <input
            id="title"
            v-model="form.title"
            type="text"
            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            required
          />
          <div v-if="form.errors.title" class="mt-1 text-sm text-red-500">
            {{ form.errors.title }}
          </div>
        </div>

        <!-- Description -->
        <div>
          <label
            for="description"
            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            Description *
          </label>
          <textarea
            id="description"
            v-model="form.description"
            rows="4"
            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            required
            minlength="20"
            placeholder="Enter a detailed description (minimum 20 characters)"
          />
          <div class="mt-1 text-xs text-gray-500">
            Minimum 20 characters required ({{ form.description.length }}/20)
          </div>
          <div v-if="form.errors.description" class="mt-1 text-sm text-red-500">
            {{ form.errors.description }}
          </div>
        </div>

        <!-- Type (conditionally shown) -->
        <div>
          <label for="type" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
            Event Type
          </label>
          <select
            v-if="!event.is_lottery"
            id="type"
            v-model="form.type"
            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            required
          >
            <option v-for="(eventType, label) in types" :key="eventType" :value="eventType">
              {{ label }}
            </option>
          </select>
          <div
            v-else
            class="rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-gray-700 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300"
          >
            {{ _('Lottery') }}
          </div>

          <div v-if="form.errors.type" class="mt-1 text-sm text-red-500">
            {{ form.errors.type }}
          </div>
        </div>

        <!-- Location -->
        <div>
          <label
            for="location"
            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            <span v-if="form.type === 'online'">Meeting URL</span>
            <span v-else>Location</span>
          </label>
          <input
            id="location"
            v-model="form.location"
            :type="form.type === 'online' ? 'url' : 'text'"
            :placeholder="
              form.type === 'online' ? 'https://zoom.us/j/...' : 'Enter physical location'
            "
            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
          />
          <div v-if="form.errors.location" class="mt-1 text-sm text-red-500">
            {{ form.errors.location }}
          </div>
        </div>

        <!-- Dates -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div>
            <label
              for="start_date"
              class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
            >
              Start Date & Time
            </label>
            <input
              id="start_date"
              v-model="form.start_date"
              type="datetime-local"
              class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            />
            <div v-if="form.errors.start_date" class="mt-1 text-sm text-red-500">
              {{ form.errors.start_date }}
            </div>
          </div>

          <div>
            <label
              for="end_date"
              class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
            >
              End Date & Time
            </label>
            <input
              id="end_date"
              v-model="form.end_date"
              type="datetime-local"
              class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            />
            <div v-if="form.errors.end_date" class="mt-1 text-sm text-red-500">
              {{ form.errors.end_date }}
            </div>
          </div>
        </div>

        <!-- Published -->
        <div class="flex items-center">
          <input
            id="is_published"
            v-model="form.is_published"
            type="checkbox"
            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700"
          />
          <label for="is_published" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
            Publish event (visible to all members)
          </label>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end space-x-3 pt-6">
          <Link
            :href="route('events.show', event.id)"
            class="px-4 py-2 text-gray-600 hover:text-gray-500 dark:text-gray-400 dark:hover:text-gray-300"
          >
            Cancel
          </Link>
          <button
            type="submit"
            :disabled="form.processing"
            class="rounded-md bg-blue-600 px-6 py-2 font-medium text-white transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
          >
            <span v-if="form.processing">Updating...</span>
            <span v-else>Update Event</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
