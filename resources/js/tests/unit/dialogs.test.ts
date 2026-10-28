import { confirm, dialogQueue, prompt, settle } from '@/lib/dialogs';

describe('questions asked from code', () => {
  it('are answered in order, one at a time', async () => {
    const first = confirm({ title: 'First?' });
    const second = prompt({ title: 'Second?', label: 'Why' });

    expect(dialogQueue.get()[0]?.options.title).toBe('First?');
    settle(true);
    expect(dialogQueue.get()[0]?.options.title).toBe('Second?');
    settle('Because');

    await expect(first).resolves.toBe(true);
    await expect(second).resolves.toBe('Because');
    expect(dialogQueue.get()).toEqual([]);
  });
});
