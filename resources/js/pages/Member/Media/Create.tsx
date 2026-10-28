import { Form, FormActions } from '@/components/forms/Form';
import { PageHeader } from '@/components/layout/PageHeader';
import { FileDrop, fileSize } from '@/design';
import { field } from '@/lib/forms';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import { useForm } from '@inertiajs/react';
import { Card, Input, Progress, Text, TextInput } from '@mantine/core';

interface Props {
  category: string | null;
  accept: Record<string, string[]>;
  maxFileKb: number;
  maxFiles: number;
  storage: { used_mb: number; limit_mb: number } | null;
}

export default function MediaCreate({ category, accept, maxFileKb, maxFiles, storage }: Props) {
  const form = useForm<{ description: string; files: File[] }>({ description: '', files: [] });
  const fileErrors = Object.entries(form.errors as Record<string, string>)
    .filter(([key]) => key.startsWith('files'))
    .map(([, message]) => message)
    .join(' ');

  return (
    <>
      <PageHeader
        title={_('Share files')}
        back={{ href: route('media.index', category ? { category } : {}), label: _('Media') }}
      />
      <Card p="lg" maw={720}>
        <Form onSubmit={() => form.post(route('media.store'), { forceFormData: true })}>
          <Input.Wrapper
            label={_('Files')}
            withAsterisk
            description={_('Up to {count} at once', { count: maxFiles })}
          >
            <div style={{ marginTop: 8 }}>
              <FileDrop
                files={form.data.files}
                onChange={(files) => form.setData('files', files)}
                accept={accept}
                multiple
                maxFiles={maxFiles}
                maxFileBytes={maxFileKb * 1024}
                error={fileErrors || undefined}
                words={{
                  prompt: _('Choose files or drop them here'),
                  limit: _('Up to {size} each', { size: fileSize(maxFileKb * 1024) }),
                  tooLarge: _('Too large'),
                  refused: (name) =>
                    _(
                      '{name} cannot be shared: photos, videos, audio, PDF and office documents only.',
                      { name },
                    ),
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
              {_('The project uses {used} of its {limit} MB until it is approved.', {
                used: storage.used_mb,
                limit: storage.limit_mb,
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
          <FormActions
            submit={_('Share')}
            processing={form.processing}
            cancelHref={route('media.index')}
          />
        </Form>
      </Card>
    </>
  );
}
