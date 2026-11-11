import { confirm, dialogQueue, prompt, settle, typedAsAsked } from '@/lib/dialogs';

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

describe('a typed confirmation', () => {
  it('takes the text as a keyboard writes it', () => {
    // A phone's curly apostrophe, an accent typed in two parts, a space too many
    expect(typedAsAsked('Cooperativa d’Ana', "Cooperativa d'Ana")).toBe(true);
    expect(typedAsAsked('José  Pedro ', 'José Pedro')).toBe(true);
    expect(typedAsAsked('“Uno”', '"Uno"')).toBe(true);
  });

  it('still asks for the text itself, its letter case too', () => {
    expect(typedAsAsked('Cooperativa', 'Cooperativa Uno')).toBe(false);
    expect(typedAsAsked('cooperativa uno', 'Cooperativa Uno')).toBe(false);
  });
});
