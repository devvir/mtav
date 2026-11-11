import { Form, OtherErrors } from '@/components/forms/Form';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { Head, useForm } from '@inertiajs/react';
import { Button, Stack, Text, Textarea, TextInput, Title } from '@mantine/core';
import { useState } from 'react';

/** An error of one of the lines (`items.2`: the third one sent) */
const ITEM = /^items\.(\d+)$/;
const FIELDS = ['name', 'items_name', 'items'];

/**
 * Where an open lottery starts: what it is called, what it distributes, and that is all.
 */
export default function Landing() {
  const form = useForm({ name: '', items_name: '', items: '' });
  const errors = form.errors as Record<string, string | undefined>;
  // Which line of the box each one sent was (empty lines are not sent), as it was sent
  const [lineOf, setLineOf] = useState<number[]>([]);

  const create = () => {
    const lines = form.data.items.split('\n').map((line) => line.trim());
    setLineOf(lines.flatMap((line, index) => (line ? [index + 1] : [])));
    form.transform((data) => ({ ...data, items: lines.filter(Boolean) }));
    form.post(route('open.store'));
  };

  // Each line refused, said by its number in the box
  const itemErrors = Object.entries(errors).flatMap(([field, message]) => {
    const sent = Number(ITEM.exec(field)?.[1] ?? NaN);
    if (Number.isNaN(sent) || !message) return [];
    return [_('Line {n}: {error}', { n: lineOf[sent] ?? sent + 1, error: message })];
  });
  const itemsError = [errors.items, ...itemErrors].filter(Boolean);

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
      <Form onSubmit={create} gap="md">
        <TextInput
          label={_('What is this for?')}
          placeholder={_('Summer house, August')}
          withAsterisk
          maxLength={80}
          value={form.data.name}
          error={errors.name}
          onChange={(event) => form.setData('name', event.target.value)}
        />
        <TextInput
          label={_('What are you sharing out?')}
          placeholder={_('Rooms')}
          withAsterisk
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
          error={
            itemsError.length ? (
              <>
                {itemsError.map((message) => (
                  <span key={message} style={{ display: 'block' }}>
                    {message}
                  </span>
                ))}
              </>
            ) : undefined
          }
          onChange={(event) => form.setData('items', event.target.value)}
        />
        <OtherErrors
          errors={errors}
          shown={(field) => FIELDS.includes(field) || ITEM.test(field)}
        />
        <Button type="submit" loading={form.processing} style={{ alignSelf: 'flex-start' }}>
          {_('Create the lottery')}
        </Button>
      </Form>
    </Stack>
  );
}
