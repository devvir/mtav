import { MediaDetails } from '@/components/domain/MediaDetails';
import { MediaGrid } from '@/components/domain/MediaGrid';
import { FilterTabs } from '@/components/feedback/FilterTabs';
import { Pagination } from '@/components/feedback/Pagination';
import { QuerySearch } from '@/components/feedback/QuerySearch';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { EmptyState } from '@/design';
import { useDetailsInAddress } from '@/lib/detailsInAddress';
import { useProjectChanges } from '@/lib/realtime';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Media, Paginated } from '@/types/models';
import { Link, usePage } from '@inertiajs/react';
import { Button, Group, Modal, Progress, Text } from '@mantine/core';
import { IconPhoto, IconPhotoPlus } from '@tabler/icons-react';

interface Props {
  media: Paginated<Media>;
  category: 'media' | 'document';
  filters: { q: string | null };
  storage: { used_mb: number; limit_mb: number } | null;
  /** A file's details, shown over the page (?file=12) */
  file: Media | null;
}

export default function MediaIndex({ media, category, filters, storage, file }: Props) {
  const details = useDetailsInAddress('file');
  const canUpload = usePage().props.project?.can.upload;
  useProjectChanges({ media: ['media', 'storage', 'file'] });

  const tabs = [
    { value: 'media', label: _('Media') },
    { value: 'document', label: _('Files') },
  ];

  return (
    <>
      <PageHeader
        title={_('Media')}
        description={_('Photos, videos, documents and audio shared in the project.')}
        help={
          <InfoTip title={_('About files')} tour="member.files">
            {_(
              'Everyone in the project can share and see them; nobody outside it can. You can edit or remove what you shared.',
            )}
          </InfoTip>
        }
        actions={
          canUpload ? (
            <Button
              data-tour="files.share"
              component={Link}
              href={route('media.create', {
                category: category === 'media' ? undefined : category,
              })}
              leftSection={<IconPhotoPlus size={18} />}
            >
              {_('Share files')}
            </Button>
          ) : null
        }
      />
      <Group justify="space-between" mb="md" gap="sm">
        <FilterTabs
          data-tour="files.tabs"
          items={tabs.map((tab) => ({
            label: tab.label,
            href: route('media.index', tab.value === 'media' ? {} : { category: tab.value }),
            active: category === tab.value,
          }))}
        />
        <QuerySearch value={filters.q} />
      </Group>
      {media.data.length ? (
        <MediaGrid media={media.data} />
      ) : (
        <EmptyState icon={IconPhoto} title={_('Nothing here yet')}>
          {canUpload ? _('Be the first to share something.') : null}
        </EmptyState>
      )}
      <Pagination page={media} />
      {storage ? (
        <div style={{ marginTop: 24, maxWidth: 420 }}>
          <Text className="ds-caption" mb={6}>
            {_('Storage used: {used} of {limit} MB (until the project is approved).', {
              used: storage.used_mb,
              limit: storage.limit_mb,
            })}
          </Text>
          <Progress
            value={(storage.used_mb / storage.limit_mb) * 100}
            radius="xl"
            size="sm"
            aria-label={_('Storage used')}
          />
        </div>
      ) : null}

      <Modal opened={Boolean(file)} onClose={details.close} title={file?.description} size="xl">
        {file ? <MediaDetails media={file} /> : null}
      </Modal>
    </>
  );
}
