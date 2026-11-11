import { Form, FormActions } from '@/components/forms/Form';
import { FileDrop } from '@/design';
import { fileSize, megabytes } from '@/lib/app';
import { field } from '@/lib/forms';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { useForm } from '@inertiajs/react';
import { Button, Input, Progress, Text, TextInput } from '@mantine/core';
import { useEffect } from 'react';

/** What may be shared, and how much */
export interface UploadLimits {
  accept: Record<string, string[]>;
  maxFileKb: number;
  /** All the files of one share together */
  maxBatchKb: number;
  maxFiles: number;
}

/** Files shared at once, under one description (in a dialog; `onDone` closes it) */
export function ShareFilesForm({
  limits,
  storage,
  onDone,
}: {
  limits: UploadLimits;
  storage: { used_mb: number; limit_mb: number } | null;
  onDone: () => void;
}) {
  const form = useForm<{ description: string; files: File[] }>({ description: '', files: [] });
  // All together, the files must fit in one request: said before sending, not after
  const total = form.data.files.reduce((sum, file) => sum + file.size, 0);
  const tooMuch = total > limits.maxBatchKb * 1024;
  const tooLarge = form.data.files.some((file) => file.size > limits.maxFileKb * 1024);
  // Closed while sending (Cancel, the ×, Escape): the files are not sent after all
  const { cancel } = form;
  useEffect(() => cancel, [cancel]);
  const fileErrors = [
    ...Object.entries(form.errors as Record<string, string>)
      .filter(([key]) => key.startsWith('files'))
      .map(([, message]) => message),
    ...(tooMuch
      ? [
          _('Together they are {size}. You can send up to {limit} at once.', {
            size: fileSize(total),
            limit: fileSize(limits.maxBatchKb * 1024),
          }),
        ]
      : []),
  ].join(' ');

  return (
    <Form
      onSubmit={() =>
        form.post(route('media.store'), { forceFormData: true, onSuccess: () => onDone() })
      }
    >
      <Input.Wrapper
        label={_('Files')}
        withAsterisk
        description={_('Up to {count} at once', { count: limits.maxFiles })}
      >
        <div style={{ marginTop: 8 }}>
          <FileDrop
            files={form.data.files}
            onChange={(files) => form.setData('files', files)}
            accept={limits.accept}
            multiple
            maxFiles={limits.maxFiles}
            maxFileBytes={limits.maxFileKb * 1024}
            error={fileErrors || undefined}
            words={{
              prompt: _('Choose files or drop them here'),
              limit: _('Up to {size} each', { size: fileSize(limits.maxFileKb * 1024) }),
              tooLarge: _('Too large'),
              size: fileSize,
              refused: (name) =>
                _(
                  '{name} cannot be shared: photos, videos, audio, PDF and office documents only.',
                  { name },
                ),
              tooMany: _('Up to {count} at once: the rest were left out.', {
                count: limits.maxFiles,
              }),
              remove: (name) => _('Remove {name}', { name }),
            }}
          />
        </div>
      </Input.Wrapper>
      <TextInput
        label={_('Description')}
        withAsterisk
        placeholder={_('Building site, week 12')}
        {...field(form, 'description')}
      />
      {storage ? (
        <Text className="ds-caption">
          {_('The project uses {used} of its {limit}.', {
            used: megabytes(storage.used_mb),
            limit: megabytes(storage.limit_mb),
          })}
        </Text>
      ) : null}
      {form.progress ? (
        <Progress
          value={form.progress.percentage ?? 0}
          radius="xl"
          animated
          aria-label={_('Uploading')}
        />
      ) : null}
      <FormActions submit={_('Share')} processing={form.processing} disabled={tooMuch || tooLarge}>
        <Button variant="default" onClick={onDone}>
          {_('Cancel')}
        </Button>
      </FormActions>
    </Form>
  );
}
