<script setup lang="ts">
import { Form } from '@/components/forms';
import {
  FormSpecs,
  FormType,
  FormUpdateEvent,
  SelectOptions,
} from '@/components/forms/types';
import { _ } from '@/composables/useTranslations';

const props = defineProps<{
  type: FormType;
  action: string;
  title: string;
  types: EventTypes;
  event?: ApiResource<Event>; // Edit-only
}>();

const typeOptions: SelectOptions = {};
Object.entries(props.types as EventTypes).forEach(
  ([value, label]) => (typeOptions[value as string] = label)
);

const eventType = ref(props.event?.type ?? 'onsite');
const startDate = ref(props.event?.start_date ?? '');
const endDate = ref(props.event?.end_date ?? '');

const specs: FormSpecs = {
  type: {
    element: 'select',
    label: 'Type',
    selected: eventType.value,
    options: typeOptions,
    required: true,
  },
  title: {
    element: 'input',
    label: 'Title',
    value: props.event?.title,
    required: true,
    minlength: 2,
  },
  description: {
    element: 'input',
    type: 'text',
    label: 'Description',
    value: props.event?.description,
    required: true,
    minlength: 20,
  },
  location: {
    element: 'input',
    label: 'Location',
    type: 'text',
    value: props.event?.location,
  },
  start_date: {
    element: 'input',
    type: 'datetime-local',
    label: 'Start Date',
    value: props.event?.start_date_raw,
  },
  end_date: {
    element: 'input',
    type: 'datetime-local',
    label: 'End Date',
    value: props.event?.end_date_raw,
  },
  is_published: {
    element: 'select',
    label: 'Published',
    selected: props.event?.is_published ? '1' : '0',
    options: {
      '0': 'Draft (private until published)',
      '1': 'Publish now (visible to all members)',
    },
    required: true,
  },
  rsvp: {
    element: 'select',
    label: 'RSVP',
    selected: props.event?.rsvp ? '1' : '0',
    options: {
      '0': 'No confirmation required',
      '1': 'Confirmation required',
    },
    required: true,
  },
};

const handleFormChange = ({ field, value }: FormUpdateEvent) => {
  if (field === 'type') {
    eventType.value = value as string;
  }

  if (field === 'start_date') {
    startDate.value = value as string;

    // Auto-set end_date if not set or if start_date is after current end_date
    if (!endDate.value || (value && new Date(value as string) >= new Date(endDate.value))) {
      // Set end_date to 2 hours after start_date
      const startDateTime = new Date(value as string);
      startDateTime.setHours(startDateTime.getHours() + 2);
      endDate.value = startDateTime.toISOString().slice(0, 16); // Format for datetime-local

      // Update the specs so the form reflects the change
      (specs.end_date as any).value = endDate.value;
    }

    // Update the specs so the form reflects the change
    (specs.start_date as any).value = startDate.value;
  }

  if (field === 'end_date') {
    endDate.value = value as string;
    (specs.end_date as any).value = endDate.value;
  }
};

// Dynamically update location field based on event type
watchEffect(() => {
  if (eventType.value === 'online') {
    (specs.location as any).label = 'Meeting URL';
    (specs.location as any).type = 'url';
  } else {
    (specs.location as any).label = 'Location';
    (specs.location as any).type = 'text';
  }
});
</script>

<template>
  <Form
    v-bind="{ type, action, params: props.event?.id, title }"
    :specs="specs"
    :buttonText="props.type === 'edit' ? undefined : 'Create Event'"
    autocomplete="off"
    @update="handleFormChange"
  >
    <template v-slot:aside>
      <h2 class="font-semibold text-foreground/60 uppercase text-shadow-2xs text-shadow-danger/20">
        {{ _('Event Guidelines') }}
      </h2>
      <ul class="list-inside list-disc text-base text-foreground/80">
        <li class="list-item leading-tight @md:leading-wide">
          {{ _('Published events will be visible to all project members') }}
        </li>
        <li class="list-item @md:leading-wide">
          {{ _('For online events, provide a valid meeting URL') }}
        </li>
        <li class="list-item @md:leading-wide">
          {{ _('RSVP allows members to confirm their attendance') }}
        </li>
      </ul>
    </template>
  </Form>
</template>