import { field } from '@/lib/forms';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Media } from '@/types/models';
import { useForm } from '@inertiajs/react';
import { Button, TextInput } from '@mantine/core';
import { Form, FormActions } from './Form';

/** A shared file's description (and an image's text for screen readers), in a dialog */
export function MediaForm({ media, onDone }: { media: Media; onDone: () => void }) {
  const form = useForm({ description: media.description, alt_text: media.alt_text ?? '' });
  const submit = () =>
    form.put(route('media.update', media.id), { preserveScroll: true, onSuccess: onDone });

  return (
    <Form onSubmit={submit}>
      <TextInput
        label={_('Description')}
        withAsterisk
        data-autofocus
        {...field(form, 'description')}
      />
      {media.category === 'image' ? (
        <TextInput
          label={_('Text for people who cannot see the image')}
          description={_('Read aloud by screen readers')}
          {...field(form, 'alt_text')}
        />
      ) : null}
      <FormActions submit={_('Save')} processing={form.processing}>
        <Button variant="default" onClick={onDone}>
          {_('Cancel')}
        </Button>
      </FormActions>
    </Form>
  );
}
