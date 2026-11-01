/**
 * Whether a message arriving moves the count of conversations with something new: one that
 * is counted already does not, and asking the server again for each message changes nothing.
 * Someone reading (the count going down) makes every conversation count again.
 */
export function unreadCount() {
  const counted = new Set<number>();
  let last: number | null = null;

  return {
    /** What the server says now */
    known(unread: number | null | undefined) {
      if (unread != null && last != null && unread < last) counted.clear();
      last = unread ?? null;
    },
    /** A message arrived out of sight: whether the count is worth asking for */
    arrives(conversation: number): boolean {
      if (counted.has(conversation)) return false;
      counted.add(conversation);
      return true;
    },
    /** A conversation in sight: read as it shows, it counts again when it leaves it */
    inSight(conversation: number) {
      counted.delete(conversation);
    },
    /** Another project: nothing known holds */
    reset() {
      counted.clear();
      last = null;
    },
  };
}
