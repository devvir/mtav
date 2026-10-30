import { DateTime } from '@/components/feedback/DateTime';
import { route, routeIs } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Media } from '@/types/models';
import { Link } from '@inertiajs/react';
import { ActionIcon, Button, Group, Slider, Text } from '@mantine/core';
import {
  IconDownload,
  IconFileText,
  IconPlayerPauseFilled,
  IconPlayerPlayFilled,
} from '@tabler/icons-react';
import { useEffect, useRef, useState } from 'react';

const SPEEDS = [1, 1.25, 1.5, 2, 0.75];

/** The one recording playing: starting another pauses it */
let playing: HTMLAudioElement | null = null;

/** A size as people read it: "820 KB", "3.4 MB" */
const size = (bytes: number) =>
  bytes >= 1024 * 1024
    ? `${Math.round((bytes / 1024 / 1024) * 10) / 10} MB`
    : `${Math.max(1, Math.round(bytes / 1024))} KB`;

/** A moment of a recording: "1:05" */
const clock = (seconds: number) =>
  `${Math.floor(seconds / 60)}:${String(Math.floor(seconds % 60)).padStart(2, '0')}`;

/** The current query (the tab, a search), with this file's details */
const detailsQuery = (id: number) => ({
  ...Object.fromEntries(new URLSearchParams(window.location.search)),
  file: id,
});

/**
 * The project's records: documents and recordings, one per row, newest first. Each opens
 * its details over the page; a recording also plays right in its row, without opening it.
 */
export function FileList({ media }: { media: Media[] }) {
  const here = routeIs('media.index');
  return (
    <ul className="file-list ds-surface">
      {media.map((item) => (
        <li key={item.id} className="row file-row">
          {item.category === 'audio' ? (
            <AudioRow item={item} here={here} />
          ) : (
            <Group wrap="nowrap" gap="md">
              <span className="file-kind" aria-hidden>
                <IconFileText size={22} stroke={1.6} />
              </span>
              <FileWords item={item} here={here} />
              <ActionIcon
                component="a"
                href={item.url}
                download
                variant="subtle"
                color="gray"
                radius="xl"
                aria-label={_('Download {name}', { name: item.description })}
              >
                <IconDownload size={18} />
              </ActionIcon>
            </Group>
          )}
        </li>
      ))}
    </ul>
  );
}

/** What a file is, who shared it and when: its words open its details */
function FileWords({ item, here }: { item: Media; here: boolean }) {
  return (
    <div style={{ flex: 1, minWidth: 0 }}>
      <Link
        href={route('media.index', here ? detailsQuery(item.id) : { file: item.id })}
        {...(here ? { only: ['file'], preserveState: true, preserveScroll: true } : {})}
        className="file-name"
      >
        {item.description}
      </Link>
      <Text className="ds-caption" truncate>
        {item.owner ? `${item.owner.name} · ` : null}
        <DateTime value={item.created_at} time={false} /> · {size(item.file_size)}
      </Text>
    </div>
  );
}

/** A recording: playing, pausing, moving through it and its speed, in the row */
function AudioRow({ item, here }: { item: Media; here: boolean }) {
  const audio = useRef<HTMLAudioElement>(null);
  const [on, setOn] = useState(false);
  const [at, setAt] = useState(0);
  const [length, setLength] = useState(0);
  const [speed, setSpeed] = useState(1);

  useEffect(() => {
    const element = audio.current;
    return () => {
      if (playing === element) playing = null;
    };
  }, []);

  const toggle = () => {
    const element = audio.current;
    if (!element) return;
    if (element.paused) {
      if (playing && playing !== element) playing.pause();
      playing = element;
      void element.play();
    } else element.pause();
  };

  const nextSpeed = () => {
    const next = SPEEDS[(SPEEDS.indexOf(speed) + 1) % SPEEDS.length];
    if (audio.current) audio.current.playbackRate = next;
    setSpeed(next);
  };

  return (
    <div data-audio={item.id}>
      <audio
        ref={audio}
        src={item.url}
        preload="metadata"
        onPlay={() => setOn(true)}
        onPause={() => setOn(false)}
        onEnded={() => setOn(false)}
        onLoadedMetadata={(event) => setLength(event.currentTarget.duration || 0)}
        onTimeUpdate={(event) => setAt(event.currentTarget.currentTime)}
      />
      <Group wrap="nowrap" gap="md">
        <ActionIcon
          size={40}
          radius="xl"
          variant="filled"
          onClick={toggle}
          aria-label={on ? _('Pause') : _('Play')}
          aria-pressed={on}
        >
          {on ? <IconPlayerPauseFilled size={18} /> : <IconPlayerPlayFilled size={18} />}
        </ActionIcon>
        <FileWords item={item} here={here} />
      </Group>
      <Group wrap="nowrap" gap="sm" mt={8} className="audio-controls">
        <Text className="ds-caption audio-time">{clock(at)}</Text>
        <Slider
          style={{ flex: 1 }}
          size="sm"
          min={0}
          max={length || 1}
          step={0.1}
          value={Math.min(at, length || 1)}
          label={clock}
          disabled={!length}
          aria-label={_('Position in the recording')}
          onChange={(value) => {
            if (audio.current) audio.current.currentTime = value;
            setAt(value);
          }}
        />
        <Text className="ds-caption audio-time">{length ? clock(length) : '–:––'}</Text>
        <Button
          variant="default"
          size="compact-xs"
          radius="xl"
          onClick={nextSpeed}
          aria-label={_('Speed: {speed}', { speed: `${speed}×` })}
          className="audio-speed"
        >
          {speed}×
        </Button>
      </Group>
    </div>
  );
}
