import { minimizeChat, showConversation, useChatProject, useChatRoute } from '@/lib/chat';
import { sendJson } from '@/lib/json';
import { heldOffline } from '@/lib/offline';
import { route } from '@/lib/route';
import { toast } from '@/lib/toasts';
import { startTour } from '@/lib/tour';
import { _ } from '@/lib/translations';
import { router } from '@inertiajs/react';
import { Button, Group, Stack } from '@mantine/core';
import { IconArrowRight, IconCheck } from '@tabler/icons-react';
import { useState } from 'react';
import { refused } from './refusals';
import type { AssistantAction } from './types';

/** The buttons under an Assistant's message; leading somewhere, the window folds out of the way */
export function AssistantActions({
  message,
  actions,
  onReply,
  onChange,
}: {
  /** The Assistant's message they are under (none for its greeting) */
  message?: number;
  actions: AssistantAction[];
  onReply: (words: string) => void;
  onChange?: () => Promise<void>;
}) {
  const { project } = useChatProject();
  const chatRoute = useChatRoute();
  const [sent, setSent] = useState<number[]>([]);
  const [busy, setBusy] = useState<number | null>(null);

  const press = async (action: AssistantAction, index: number) => {
    if (action.type === 'reply') return onReply(action.label);
    if (heldOffline()) return undefined;

    // Writing to an admin: their conversation opens right here, in the chat
    // (only in a project: outside one there is nobody to write to, and the server offers none)
    if (action.type === 'message' && project) {
      setBusy(index);
      const response = await sendJson('POST', route('chat.admin', { project: project.id }), {});
      setBusy(null);
      if (!response.ok) return refused();
      const { conversation } = (await response.json()) as { conversation: number };
      showConversation(conversation);
      return onChange?.();
    }

    if (action.type === 'report' && message) {
      setBusy(index);
      const response = await sendJson('POST', chatRoute('report', { message }), {
        action: index,
      });
      setBusy(null);
      if (response.status === 429) {
        return toast({
          tone: 'warning',
          message: ((await response.json()) as { message: string }).message,
        });
      }
      if (!response.ok) return refused();
      setSent((done) => [...done, index]);
      return toast({ tone: 'success', message: _('Sent. Thank you for telling us.') });
    }

    minimizeChat();
    router.visit(action.href!, {
      onSuccess: () => {
        if (action.tour) startTour(action.tour);
      },
    });
    return undefined;
  };

  return (
    <Stack gap="xs" className="chat-actions">
      {actions
        .map((action, index) => ({ action, index }))
        .filter(({ action }) => action.type === 'report')
        .map(({ action, index }) => (
          <blockquote key={`report-${index}`} className="chat-report">
            {action.message}
          </blockquote>
        ))}
      <Group gap="xs">
        {actions.map((action, index) => {
          const done = action.type === 'report' && (action.sent || sent.includes(index));
          return (
            <Button
              key={`${action.type}-${action.label}`}
              size="xs"
              variant={action.type === 'reply' ? 'default' : 'light'}
              rightSection={
                done ? (
                  <IconCheck size={14} />
                ) : action.type === 'reply' ? null : (
                  <IconArrowRight size={14} />
                )
              }
              loading={busy === index}
              disabled={done}
              onClick={() => void press(action, index)}
            >
              {done ? _('Sent') : action.label}
            </Button>
          );
        })}
      </Group>
    </Stack>
  );
}
