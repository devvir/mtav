import { route, routeIs } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Media } from '@/types/models';
import { Link } from '@inertiajs/react';
import { VisuallyHidden } from '@mantine/core';
import { IconFileText, IconMusic, IconPlayerPlayFilled } from '@tabler/icons-react';

/** The current query (the tab, a search), with this file's details */
const detailsQuery = (id: number) => ({
  ...Object.fromEntries(new URLSearchParams(window.location.search)),
  file: id,
});

/**
 * Files as tiles: photos and videos by their thumbnail, the rest by their kind and words.
 * Each opens its details over the media page (without reloading it, when already there).
 */
export function MediaGrid({ media }: { media: Media[] }) {
  const here = routeIs('media.index');
  return (
    <ul className="media-grid">
      {media.map((item) => {
        const KindIcon = item.category === 'audio' ? IconMusic : IconFileText;
        return (
          <li key={item.id}>
            <Link
              href={route('media.index', here ? detailsQuery(item.id) : { file: item.id })}
              {...(here ? { only: ['file'], preserveState: true, preserveScroll: true } : {})}
              className="media-tile ds-surface"
            >
              {item.thumbnail ? (
                <img src={item.thumbnail} alt={item.alt_text ?? item.description} loading="lazy" />
              ) : (
                <span className="media-kind">
                  <KindIcon size={38} stroke={1.5} />
                  <span>{item.description}</span>
                </span>
              )}
              {item.category === 'video' ? (
                <span className="media-play" aria-hidden>
                  <IconPlayerPlayFilled size={22} />
                </span>
              ) : null}
              <VisuallyHidden>{_('Open {name}', { name: item.description })}</VisuallyHidden>
            </Link>
          </li>
        );
      })}
    </ul>
  );
}
