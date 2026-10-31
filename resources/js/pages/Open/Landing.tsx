import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { Head, useForm } from '@inertiajs/react';
import { Button, Stack, Text, Textarea, TextInput, Title } from '@mantine/core';
import type { FormEvent } from 'react';

/**
 * Where an open lottery starts: what it is called, what it distributes, and that is all.
 */
export default function Landing() {
  const form = useForm({ name: '', items_name: '', items: '' });
  const errors = form.errors as Record<string, string | undefined>;

  const create = (event: FormEvent) => {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      items: data.items
        .split('\n')
        .map((line) => line.trim())
        .filter(Boolean),
    }));
    form.post(route('open.store'));
  };

  return (
    <Stack gap="xl">
      <Head title={_('Share things out fairly')} />
      <Stack gap="xs">
        <Title order={1}>{_('Share things out fairly')}</Title>
        <Text c="dimmed">
          {_(
            'Name what you are sharing out and send the link. Everyone says what they prefer, and the lottery gives each person the best it can. No sign-up.',
          )}
        </Text>
      </Stack>
      <form onSubmit={create}>
        <Stack>
          <TextInput
            label={_('What is this for?')}
            placeholder={_('Summer house, August')}
            required
            maxLength={80}
            value={form.data.name}
            error={errors.name}
            onChange={(event) => form.setData('name', event.target.value)}
          />
          <TextInput
            label={_('What are you sharing out?')}
            placeholder={_('Rooms')}
            required
            maxLength={40}
            value={form.data.items_name}
            error={errors.items_name}
            onChange={(event) => form.setData('items_name', event.target.value)}
          />
          <Textarea
            label={_('Which ones?')}
            description={_('One per line. You can add more later.')}
            autosize
            minRows={4}
            maxRows={12}
            value={form.data.items}
            error={errors.items ?? errors['items.0']}
            onChange={(event) => form.setData('items', event.target.value)}
          />
          <Button type="submit" loading={form.processing} style={{ alignSelf: 'flex-start' }}>
            {_('Create the lottery')}
          </Button>
        </Stack>
      </form>
    </Stack>
  );
}
