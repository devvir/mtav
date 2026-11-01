import { unreadCount } from '@/lib/unreadCount';
import { describe, expect, it } from 'vitest';

describe('the count of conversations with something new', () => {
  it('is asked for once per conversation, however many messages it gets', () => {
    const count = unreadCount();

    expect(count.arrives(1)).toBe(true);
    expect(count.arrives(1)).toBe(false);
    expect(count.arrives(1)).toBe(false);
    expect(count.arrives(2)).toBe(true);
  });

  it('is asked for again once someone reads', () => {
    const count = unreadCount();
    count.known(2);
    count.arrives(1);

    count.known(1);

    expect(count.arrives(1)).toBe(true);
  });

  it('is not asked for again as it grows or stays', () => {
    const count = unreadCount();
    count.known(0);
    count.arrives(1);

    count.known(1);
    count.known(1);

    expect(count.arrives(1)).toBe(false);
  });

  it('is asked for again after a conversation was in sight', () => {
    const count = unreadCount();
    count.arrives(1);

    count.inSight(1);

    expect(count.arrives(1)).toBe(true);
  });

  it('knows nothing of another project', () => {
    const count = unreadCount();
    count.known(3);
    count.arrives(1);

    count.reset();
    count.known(1);

    expect(count.arrives(1)).toBe(true);
    expect(count.arrives(2)).toBe(true);
  });
});
