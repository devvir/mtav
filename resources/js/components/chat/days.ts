import { formatDate } from '@/components/feedback/DateTime';
import { _ } from '@/lib/translations';

/** The day a message was sent on, to tell where a new day starts among them */
export const dayOf = (value: string) => new Date(value).toDateString();

/** A day's heading among the messages: today, yesterday, or its date */
export function dayLabel(value: string) {
  const day = new Date(value);
  const today = new Date();
  const yesterday = new Date(Date.now() - 86_400_000);
  if (day.toDateString() === today.toDateString()) return _('Today');
  if (day.toDateString() === yesterday.toDateString()) return _('Yesterday');
  return formatDate(value, false);
}
