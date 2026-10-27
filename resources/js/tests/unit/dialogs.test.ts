import { confirm, prompt, useDialogQueue } from '@/composables/useDialogs';
import { useToasts } from '@/composables/useToasts';

describe('dialogs asked from code', () => {
  it('answer in order, one at a time', async () => {
    const queue = useDialogQueue();

    const first = confirm({ title: 'First?' });
    const second = prompt({ title: 'Second?', label: 'Why' });

    expect(queue.current()?.options.title).toBe('First?');
    queue.settle(true);
    expect(queue.current()?.options.title).toBe('Second?');
    queue.settle('Because');

    await expect(first).resolves.toBe(true);
    await expect(second).resolves.toBe('Because');
    expect(queue.current()).toBeNull();
  });
});

describe('toasts', () => {
  beforeEach(() => vi.useFakeTimers());
  afterEach(() => vi.useRealTimers());

  it('go away on their own, except errors', () => {
    const { toasts, show } = useToasts();
    toasts.splice(0);

    show({ tone: 'success', message: 'Saved' });
    show({ tone: 'error', message: 'Failed' });
    vi.advanceTimersByTime(10_000);

    expect(toasts.map((toast) => toast.message)).toEqual(['Failed']);
  });
});
