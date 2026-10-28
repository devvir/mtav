import { EventForm } from '@/components/forms/EventForm';
import { PageHeader } from '@/components/layout/PageHeader';
import { route } from '@/lib/route';
import { _ } from '@/lib/translations';

export default function EventCreate() {
  return (
    <>
      <PageHeader
        title={_('Create an event')}
        back={{ href: route('manage.events.index'), label: _('Events') }}
      />
      <EventForm />
    </>
  );
}
