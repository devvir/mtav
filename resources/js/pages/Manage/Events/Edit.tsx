import { EventForm } from '@/components/forms/EventForm';
import { PageHeader } from '@/components/layout/PageHeader';
import { confirm } from '@/lib/dialogs';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';
import type { ProjectEvent } from '@/types/models';
import { router } from '@inertiajs/react';
import { Button } from '@mantine/core';
import { IconTrash } from '@tabler/icons-react';

export default function EventEdit({ event }: { event: ProjectEvent }) {
  const cancel = async () => {
    const ok = await confirm({
      title: _('Cancel {title}?', { title: event.title }),
      message: _('The event is removed for everyone.'),
      confirmLabel: _('Cancel the event'),
      cancelLabel: _('Keep it'),
      danger: true,
    });
    if (ok) router.delete(route('manage.events.destroy', event.id));
  };

  return (
    <>
      <PageHeader
        title={event.title}
        back={{ href: route('manage.events.index'), label: _('Events') }}
        actions={
          <Button
            variant="default"
            color="red"
            c="red"
            leftSection={<IconTrash size={18} />}
            onClick={cancel}
          >
            {_('Cancel the event')}
          </Button>
        }
      />
      <EventForm event={event} />
    </>
  );
}
