import { toast } from '@/lib/toasts';
import { _ } from '@/lib/translations';

/** What the chat says when the server turns something down */

export const refused = () => toast({ tone: 'error', message: _("That didn't work. Try again.") });

/** A change to a message turned down: past its hour, the server says so; anything else, try again */
export const turnedDown = async (response: Response) => {
  if (response.status !== 409) return refused();
  const { message } = (await response.json()) as { message: string };
  return toast({ tone: 'warning', message });
};
