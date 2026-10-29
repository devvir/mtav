import { DateTime } from '@/components/feedback/DateTime';
import { MediaForm } from '@/components/forms/MediaForm';
import { confirm } from '@/lib/dialogs';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Media } from '@/types/models';
import { router } from '@inertiajs/react';
import { ActionIcon, Button, Card, Group, Text } from '@mantine/core';
import {
  IconChevronLeft,
  IconChevronRight,
  IconDownload,
  IconPencil,
  IconTrash,
} from '@tabler/icons-react';
import { useEffect, useRef, useState } from 'react';

/** A shared file: seen or played where the browser can, downloaded otherwise; its owner
 * (and admins) edit its description or remove it. Given its neighbours, photos and videos
 * go one to the next: arrows on the stage, the keyboard's arrows, a swipe. */
export function MediaDetails({
  media,
  previous,
  next,
  position,
  onMove,
}: {
  media: Media;
  previous?: Media | null;
  next?: Media | null;
  /** Where it is among those moved through: [3, 12] */
  position?: [number, number];
  onMove?: (to: Media) => void;
}) {
  const [editing, setEditing] = useState(false);
  const swipe = useRef<{ x: number; y: number } | null>(null);

  // The keyboard's arrows, unless typing, or a player is being worked
  useEffect(() => {
    if (!onMove || editing) return undefined;
    const onKey = (event: KeyboardEvent) => {
      const target = event.target as HTMLElement;
      if (target.closest('input, textarea, select, video, audio, [contenteditable]')) return;
      const to = event.key === 'ArrowLeft' ? previous : event.key === 'ArrowRight' ? next : null;
      if (!to) return;
      event.preventDefault();
      onMove(to);
    };
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [onMove, previous, next, editing]);

  // The next photos come at once: fetched while this one is seen
  useEffect(() => {
    [previous, next].forEach((neighbour) => {
      if (neighbour?.category === 'image') new Image().src = neighbour.url;
    });
  }, [previous, next]);
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

  // Editing replaces the details in the same dialog (one dialog at a time)
  if (editing) return <MediaForm media={media} onDone={() => setEditing(false)} />;

  return (
    <>
      <Card
        p={0}
        className="media-stage"
        // A sideways swipe (a finger or a pen: a mouse uses the arrows)
        onPointerDown={(event) => {
          if (event.pointerType !== 'mouse') swipe.current = { x: event.clientX, y: event.clientY };
        }}
        onPointerUp={(event) => {
          const from = swipe.current;
          swipe.current = null;
          if (!from || !onMove) return;
          const [dx, dy] = [event.clientX - from.x, event.clientY - from.y];
          if (Math.abs(dx) < 50 || Math.abs(dx) < Math.abs(dy) * 1.5) return;
          const to = dx < 0 ? next : previous;
          if (to) onMove(to);
        }}
        onPointerCancel={() => (swipe.current = null)}
      >
        {onMove && previous ? (
          <ActionIcon
            className="media-step"
            data-side="previous"
            size="xl"
            radius="xl"
            aria-label={_('Previous')}
            onClick={() => onMove(previous)}
          >
            <IconChevronLeft size={24} />
          </ActionIcon>
        ) : null}
        {onMove && next ? (
          <ActionIcon
            className="media-step"
            data-side="next"
            size="xl"
            radius="xl"
            aria-label={_('Next')}
            onClick={() => onMove(next)}
          >
            <IconChevronRight size={24} />
          </ActionIcon>
        ) : null}
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
        {position && position[1] > 1
          ? `${_('{position} of {count}', { position: position[0], count: position[1] })} · `
          : null}
        {media.owner ? `${_('Shared by {name}', { name: media.owner.name })} · ` : null}
        <DateTime value={media.created_at} />
      </Text>
      <Group gap="sm" mt="lg">
        {download}
        {media.can.update ? (
          <Button
            variant="default"
            leftSection={<IconPencil size={18} />}
            onClick={() => setEditing(true)}
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
      </Group>
    </>
  );
}
