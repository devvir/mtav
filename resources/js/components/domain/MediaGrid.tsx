import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { Media } from '@/types/models';
import { Link } from '@inertiajs/react';
import { VisuallyHidden } from '@mantine/core';
import { IconFileText, IconMusic, IconPlayerPlayFilled } from '@tabler/icons-react';

/** Files as tiles: photos and videos by their thumbnail, the rest by their kind and words */
export function MediaGrid({ media }: { media: Media[] }) {
  return (
    <ul className="media-grid">
      {media.map((item) => {
        const KindIcon = item.category === 'audio' ? IconMusic : IconFileText;
        return (
          <li key={item.id}>
            <Link href={route('media.show', item.id)} className="media-tile ds-surface">
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
