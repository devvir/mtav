import { useEffect, useMemo, useState } from 'react';

/**
 * A conversation's messages as they change under the reader's eyes: deleted ones leave
 * (fading, their room closing), edited ones show their new words, and someone's own can be
 * changed only until the hour after sending closes, by itself.
 */
export interface ChangeableMessage {
  id: number;
  body: string;
  /** Theirs: until when they may still edit or delete it (none: never, or no more) */
  changeable_until?: string | null;
}

/** How long a deleted message takes to leave */
export const LEAVING_MS = 260;

/**
 * The messages gone between two answers of the server: those missing from the newer one
 * within the stretch it covers. The oldest no longer on its page are not gone, only further up.
 */
export function goneBetween<T extends { id: number }>(before: T[], after: T[]): T[] {
  const from = after[0]?.id ?? -Infinity;
  const kept = new Set(after.map((message) => message.id));
  return before.filter((message) => message.id >= from && !kept.has(message.id));
}

/** The messages whose words changed between two answers */
export function rewordedBetween(before: ChangeableMessage[], after: ChangeableMessage[]): number[] {
  const was = new Map(before.map((message) => [message.id, message.body]));
  return after
    .filter((message) => was.has(message.id) && was.get(message.id) !== message.body)
    .map((message) => message.id);
}

/** Whether its writer may still edit or delete it, at this moment */
export const changeableAt = (message: ChangeableMessage, now: number): boolean =>
  Boolean(message.changeable_until) && Date.parse(message.changeable_until!) > now;

/** How long until the next of these can change no more, if any still can */
export function nextClosing(messages: ChangeableMessage[], now: number): number | null {
  const closing = messages
    .filter((message) => changeableAt(message, now))
    .map((message) => Date.parse(message.changeable_until!) - now);
  return closing.length ? Math.min(...closing) : null;
}

/**
 * The moment the page goes by for what can still change: it moves on by itself as each
 * message's hour closes, so no button outlives it
 */
export function useChangeClock(messages: ChangeableMessage[]): number {
  const [now, setNow] = useState(() => Date.now());

  useEffect(() => {
    const wait = nextClosing(messages, Date.now());
    if (wait === null) return undefined;
    const timer = window.setTimeout(() => setNow(Date.now()), wait + 50);
    return () => window.clearTimeout(timer);
  }, [messages, now]);

  return now;
}

/**
 * What a conversation shows: its messages, with those just deleted still there a moment as
 * they leave, and which ones were just reworded
 */
export function useShownMessages<T extends ChangeableMessage>(messages: T[]) {
  const [before, setBefore] = useState(messages);
  const [leaving, setLeaving] = useState<T[]>([]);
  const [reworded, setReworded] = useState<ReadonlySet<number>>(() => new Set());

  // Worked out as the new answer comes, so nothing vanishes for a frame before it leaves
  if (messages !== before) {
    setBefore(messages);
    const gone = goneBetween(before, messages);
    const kept = new Set(messages.map((message) => message.id));
    if (gone.length) setLeaving((still) => [...still.filter((one) => !kept.has(one.id)), ...gone]);
    const changed = rewordedBetween(before, messages);
    if (changed.length) setReworded(new Set(changed));
  }

  useEffect(() => {
    if (!leaving.length) return undefined;
    const timer = window.setTimeout(() => setLeaving([]), LEAVING_MS);
    return () => window.clearTimeout(timer);
  }, [leaving]);

  const shown = useMemo(
    () =>
      [
        ...messages.map((message) => ({ message, leaving: false })),
        ...leaving.map((message) => ({ message, leaving: true })),
      ].sort((one, other) => one.message.id - other.message.id),
    [messages, leaving],
  );

  return { shown, reworded };
}
