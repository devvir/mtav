import { PageHeader as Header } from '@/design';
import { autoTour, hasTour } from '@/lib/tour';
import { Head, Link } from '@inertiajs/react';
import { Anchor } from '@mantine/core';
import { IconArrowLeft } from '@tabler/icons-react';
import { useEffect, type ReactNode } from 'react';

/**
 * The top of a page — also its title in the browser: the way back, a help mark, actions.
 * Given its `tour`, the page's tour plays by itself the first time someone arrives.
 */
export function PageHeader({
  title,
  description,
  back,
  help,
  tour,
  actions,
}: {
  title: string;
  description?: ReactNode;
  back?: { href: string; label: string };
  help?: ReactNode;
  tour?: string;
  actions?: ReactNode;
}) {
  useEffect(() => (tour && hasTour(tour) ? autoTour(tour) : undefined), [tour]);

  return (
    <>
      <Head title={title} />
      <Header
        title={title}
        description={description}
        help={help}
        actions={actions}
        back={
          back ? (
            <Anchor
              component={Link}
              href={back.href}
              c="dimmed"
              fw={600}
              size="sm"
              className="page-back"
            >
              <IconArrowLeft size={16} /> {back.label}
            </Anchor>
          ) : null
        }
      />
    </>
  );
}
