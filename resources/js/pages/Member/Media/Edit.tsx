import { Form, FormActions } from '@/components/forms/Form';
import { PageHeader } from '@/components/layout/PageHeader';
import { field } from '@/lib/forms';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Media } from '@/types/models';
import { useForm } from '@inertiajs/react';
import { Card, TextInput } from '@mantine/core';

export default function MediaEdit({ media }: { media: Media }) {
  const form = useForm({ description: media.description, alt_text: media.alt_text ?? '' });

  return (
    <>
      <PageHeader
        title={_('Edit the description')}
        back={{ href: route('media.show', media.id), label: media.description }}
      />
      <Card p="lg" maw={720}>
        <Form onSubmit={() => form.put(route('media.update', media.id))}>
          <TextInput label={_('Description')} withAsterisk {...field(form, 'description')} />
          {media.category === 'image' ? (
            <TextInput
              label={_('Text for people who cannot see the image')}
              description={_('Read aloud by screen readers')}
              {...field(form, 'alt_text')}
            />
          ) : null}
          <FormActions
            submit={_('Save')}
            processing={form.processing}
            cancelHref={route('media.show', media.id)}
          />
        </Form>
      </Card>
    </>
  );
}
