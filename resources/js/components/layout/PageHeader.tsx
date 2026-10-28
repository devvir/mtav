import { PageHeader as Header } from '@/design';
import { Head, Link } from '@inertiajs/react';
import { Anchor } from '@mantine/core';
import { IconArrowLeft } from '@tabler/icons-react';
import type { ReactNode } from 'react';

/** The top of a page — also its title in the browser: the way back, a help mark, actions */
export function PageHeader({
  title,
  description,
  back,
  help,
  actions,
}: {
  title: string;
  description?: ReactNode;
  back?: { href: string; label: string };
  help?: ReactNode;
  actions?: ReactNode;
}) {
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
