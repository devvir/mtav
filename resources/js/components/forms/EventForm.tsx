import { checkbox, field } from '@/lib/forms';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { ProjectEvent } from '@/types/models';
import { useForm } from '@inertiajs/react';
import {
  Card,
  Checkbox,
  Input,
  SimpleGrid,
  Stack,
  TextInput,
  Textarea,
  UnstyledButton,
} from '@mantine/core';
import { IconMapPin, IconVideo } from '@tabler/icons-react';
import { Form, FormActions } from './Form';

/** <input type="datetime-local"> wants local time without a zone */
const local = (iso: string | null | undefined) => {
  if (!iso) return '';
  const date = new Date(iso);
  return new Date(date.getTime() - date.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
};

export function EventForm({ event }: { event?: ProjectEvent }) {
  const form = useForm({
    type: (event?.type ?? 'onsite') as 'onsite' | 'online',
    title: event?.title ?? '',
    description: event?.description ?? '',
    location: event?.location ?? '',
    start_date: local(event?.start_date),
    end_date: local(event?.end_date),
    is_published: event?.is_published ?? true,
    allows_rsvp: event?.allows_rsvp ?? false,
  });

  const submit = () => {
    form.transform((data) => ({
      ...data,
      start_date: data.start_date ? new Date(data.start_date).toISOString() : null,
      end_date: data.end_date ? new Date(data.end_date).toISOString() : null,
    }));
    if (event) form.put(route('manage.events.update', event.id));
    else form.post(route('manage.events.store'));
  };

  const kinds = [
    { value: 'onsite' as const, label: _('On site'), icon: IconMapPin },
    { value: 'online' as const, label: _('Online'), icon: IconVideo },
  ];

  return (
    <Card p="lg" maw={720}>
      <Form onSubmit={submit}>
        <Input.Wrapper label={_('Where')}>
          <SimpleGrid cols={2} mt={4}>
            {kinds.map((kind) => (
              <UnstyledButton
                key={kind.value}
                className="choice-tile ds-surface"
                data-active={form.data.type === kind.value || undefined}
                aria-pressed={form.data.type === kind.value}
                onClick={() => form.setData('type', kind.value)}
                style={{ justifyContent: 'center' }}
              >
                <kind.icon size={20} /> {kind.label}
              </UnstyledButton>
            ))}
          </SimpleGrid>
        </Input.Wrapper>
        <TextInput label={_('Title')} withAsterisk autoFocus {...field(form, 'title')} />
        <Textarea
          label={_('Description')}
          withAsterisk
          autosize
          minRows={3}
          {...field(form, 'description')}
        />
        <TextInput
          label={form.data.type === 'online' ? _('Link to join') : _('Place')}
          description={form.data.type === 'online' ? _('Starting with https://') : undefined}
          type={form.data.type === 'online' ? 'url' : 'text'}
          {...field(form, 'location')}
        />
        <SimpleGrid cols={{ base: 1, sm: 2 }}>
          <TextInput
            label={_('Starts')}
            description={_('Empty if not decided')}
            type="datetime-local"
            {...field(form, 'start_date')}
          />
          <TextInput
            label={_('Ends')}
            description={_('One hour if empty')}
            type="datetime-local"
            min={form.data.start_date || undefined}
            {...field(form, 'end_date')}
          />
        </SimpleGrid>
        <Stack gap="xs">
          <Checkbox
            label={_('Published')}
            description={_('Drafts are visible to admins only.')}
            {...checkbox(form, 'is_published')}
          />
          <Checkbox
            label={_('Ask people whether they will go')}
            {...checkbox(form, 'allows_rsvp')}
          />
        </Stack>
        <FormActions
          submit={event ? _('Save') : _('Create the event')}
          processing={form.processing}
          cancelHref={route('manage.events.index')}
        />
      </Form>
    </Card>
  );
}
