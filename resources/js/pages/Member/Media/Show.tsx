import { DateTime } from '@/components/feedback/DateTime';
import { PageHeader } from '@/components/layout/PageHeader';
import { confirm } from '@/lib/dialogs';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Media } from '@/types/models';
import { Link, router } from '@inertiajs/react';
import { Button, Card, Text } from '@mantine/core';
import { IconDownload, IconPencil, IconTrash } from '@tabler/icons-react';

export default function MediaShow({ media }: { media: Media }) {
  const remove = async () => {
    if (
      await confirm({
        title: _('Remove this file?'),
        message: media.description,
        confirmLabel: _('Remove'),
        danger: true,
      })
    ) {
      router.delete(route('media.destroy', media.id));
    }
  };
  const download = (
    <Button
      variant="default"
      component="a"
      href={media.url}
      download
      leftSection={<IconDownload size={18} />}
    >
      {_('Download')}
    </Button>
  );

  return (
    <>
      <PageHeader
        title={media.description}
        back={{
          href: route('media.index', media.category === 'document' ? { category: 'document' } : {}),
          label: _('Media'),
        }}
        actions={
          <>
            {download}
            {media.can.update ? (
              <Button
                variant="default"
                component={Link}
                href={route('media.edit', media.id)}
                leftSection={<IconPencil size={18} />}
              >
                {_('Edit')}
              </Button>
            ) : null}
            {media.can.delete ? (
              <Button
                variant="default"
                color="red"
                c="red"
                onClick={remove}
                leftSection={<IconTrash size={18} />}
              >
                {_('Remove')}
              </Button>
            ) : null}
          </>
        }
      />
      <Card p={0} className="media-stage">
        {media.category === 'image' ? (
          <img src={media.url} alt={media.alt_text ?? media.description} />
        ) : media.category === 'video' ? (
          <video src={media.url} controls />
        ) : media.category === 'audio' ? (
          <div style={{ padding: 24 }}>
            <audio src={media.url} controls style={{ width: '100%' }} />
          </div>
        ) : media.mime_type === 'application/pdf' ? (
          <iframe src={media.url} title={media.description} />
        ) : (
          <div style={{ padding: 32, textAlign: 'center' }}>
            <Button
              component="a"
              href={media.url}
              download
              leftSection={<IconDownload size={18} />}
            >
              {_('Download the document')}
            </Button>
          </div>
        )}
      </Card>
      <Text className="ds-caption" mt="sm">
        {media.owner ? `${_('Shared by {name}', { name: media.owner.name })} · ` : null}
        <DateTime value={media.created_at} />
      </Text>
    </>
  );
}
