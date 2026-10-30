import { ArchivedList, ArchiveSwitch, type ArchiveState } from '@/components/domain/Archive';
import { FileList } from '@/components/domain/FileList';
import { MediaDetails } from '@/components/domain/MediaDetails';
import { MediaGrid } from '@/components/domain/MediaGrid';
import { FilterTabs } from '@/components/feedback/FilterTabs';
import { Pagination } from '@/components/feedback/Pagination';
import { QuerySearch } from '@/components/feedback/QuerySearch';
import { ShareFilesForm, type UploadLimits } from '@/components/forms/ShareFilesForm';
import { InfoTip } from '@/components/help/InfoTip';
import { PageHeader } from '@/components/layout/PageHeader';
import { EmptyState } from '@/design';
import { useAddressedDialog } from '@/lib/addressedDialog';
import { megabytes } from '@/lib/app';
import { useDetailsInAddress } from '@/lib/detailsInAddress';
import { useProjectChanges } from '@/lib/realtime';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Media, Paginated } from '@/types/models';
import { usePage } from '@inertiajs/react';
import { Button, Group, Modal, Progress, Text } from '@mantine/core';
import { IconPhoto, IconPhotoPlus } from '@tabler/icons-react';
import { useState } from 'react';

interface Props {
  media: Paginated<Media>;
  category: 'media' | 'files';
  filters: { q: string | null };
  archive: ArchiveState | null;
  storage: { used_mb: number; limit_mb: number } | null;
  /** What may be shared, for those who may (the share dialog, ?share=1) */
  upload: UploadLimits | null;
  /** A file's details, shown over the page (?file=12) */
  file: Media | null;
  /** Where the photo or video shown stands among all of them (null: not one) */
  carousel: {
    position: number;
    count: number;
    previous: number | null;
    next: number | null;
  } | null;
}

export default function MediaIndex({
  media,
  category,
  filters,
  archive,
  storage,
  upload,
  file,
  carousel,
}: Props) {
  const details = useDetailsInAddress('file', ['carousel']);
  const features = usePage().props.project!.features;
  const canUpload = usePage().props.project?.can.upload;
  const sharing = useAddressedDialog('share', Boolean(upload));
  useProjectChanges({ media: ['media', 'storage', 'file', 'carousel'] });

  // Moving through photos shows the next one at once when this page lists it, while the
  // address follows (until the details shown change: the next one arrived, or others
  // were opened); one beyond the page comes with the address
  const [moved, setMoved] = useState<{ from: number; to: Media } | null>(null);
  const shown = file && moved?.from === file.id ? moved.to : file;
  const listed = (id: number | null) => media.data.find((item) => item.id === id) ?? null;
  const move = (id: number | null) =>
    id
      ? () => {
          const next = listed(id);
          if (file && next) setMoved({ from: file.id, to: next });
          details.move(id);
        }
      : undefined;
  // The neighbours known are the file's (the server's), not yet the one just moved to
  const steady = shown?.id === file?.id ? carousel : null;

  const tabs = [
    { value: 'media', label: _('Media') },
    { value: 'files', label: _('Files') },
  ];

  return (
    <>
      <PageHeader
        tour="member.files"
        title={_('Media')}
        description={_(
          "The project's photos and videos, and its records: documents and recordings.",
        )}
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
              onClick={sharing.open}
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
          items={tabs
            .filter((tab) => features[tab.value === 'media' ? 'gallery' : 'files'])
            .map((tab) => ({
              label: tab.label,
              href: route('media.index', tab.value === 'media' ? {} : { category: tab.value }),
              active: category === tab.value,
            }))}
        />
        <Group gap="md">
          <ArchiveSwitch archive={archive} />
          <QuerySearch value={filters.q} />
        </Group>
      </Group>
      {archive?.shown ? (
        <ArchivedList archive={archive} kind={category === 'files' ? 'files' : 'gallery'} />
      ) : media.data.length ? (
        category === 'files' ? (
          <FileList media={media.data} />
        ) : (
          <MediaGrid media={media.data} />
        )
      ) : (
        <EmptyState icon={IconPhoto} title={_('Nothing here yet')}>
          {canUpload ? _('Be the first to share something.') : null}
        </EmptyState>
      )}
      <Pagination page={media} />
      {storage ? (
        <div style={{ marginTop: 24, maxWidth: 420 }}>
          <Text className="ds-caption" mb={6}>
            {_('Storage used: {used} of {limit}.', {
              used: megabytes(storage.used_mb),
              limit: megabytes(storage.limit_mb),
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

      <Modal opened={Boolean(file)} onClose={details.close} title={shown?.description} size="xl">
        {shown ? (
          <MediaDetails
            key={shown.id}
            media={shown}
            onPrevious={move(steady?.previous ?? null)}
            onNext={move(steady?.next ?? null)}
            preload={[listed(steady?.previous ?? null), listed(steady?.next ?? null)].filter(
              (item): item is Media => item !== null,
            )}
            position={steady ? [steady.position, steady.count] : undefined}
          />
        ) : null}
      </Modal>

      {upload ? (
        <Modal opened={sharing.opened} onClose={sharing.close} title={_('Share files')} size="lg">
          <ShareFilesForm limits={upload} storage={storage} onDone={sharing.close} />
        </Modal>
      ) : null}
    </>
  );
}
