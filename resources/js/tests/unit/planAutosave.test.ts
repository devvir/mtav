import { notSaved, SaveRefused, useAutosave } from '@/components/plan/autosave';
import { PlanModel } from '@/components/plan/model';
import type { Plan } from '@/types/models';
import { act, renderHook } from '@testing-library/react';
import { useSyncExternalStore } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const plan = (): Plan => ({
  id: 1,
  width: 800,
  height: 600,
  unit_system: 'meters',
  polygon: [
    [0, 0],
    [800, 0],
    [800, 600],
    [0, 600],
  ],
  items: [],
});

/** The editor's model saving itself through `save` */
const draw = (save: () => Promise<Record<string, number>>) => {
  const model = new PlanModel(plan());
  const sent = vi.fn(save);
  const { result } = renderHook(() =>
    useAutosave(model, useSyncExternalStore(model.subscribe, model.getVersion), sent),
  );
  return { model, sent, result };
};

const wait = (ms: number) => act(() => vi.advanceTimersByTimeAsync(ms));

describe('the plan saving itself', () => {
  beforeEach(() => {
    vi.useFakeTimers();
    localStorage.clear();
  });
  afterEach(() => vi.useRealTimers());

  it('saves a moment after a change', async () => {
    const { model, sent, result } = draw(() => Promise.resolve({}));

    act(() => model.add('green'));
    await wait(1200);

    expect(sent).toHaveBeenCalledTimes(1);
    expect(result.current.status).toBe('saved');
  });

  it('tries again a few seconds later when the server could not be reached', async () => {
    const answers = [() => Promise.reject(new Error('No connection')), () => Promise.resolve({})];
    const { model, sent, result } = draw(() => answers.shift()!());

    act(() => model.add('green'));
    await wait(1200);
    expect(result.current).toMatchObject({ status: 'failed', reason: null });

    await wait(5000);
    expect(sent).toHaveBeenCalledTimes(2);
    expect(result.current.status).toBe('saved');
  });

  it('says why the server refused it, and sends it again only once something changes', async () => {
    const { model, sent, result } = draw(() =>
      Promise.reject(new SaveRefused('The floor must be between -5 and 100.')),
    );

    act(() => model.add('green'));
    await wait(1200);
    await wait(60_000);

    expect(sent).toHaveBeenCalledTimes(1);
    expect(result.current).toMatchObject({
      status: 'failed',
      reason: 'The floor must be between -5 and 100.',
    });

    act(() => model.add('street'));
    await wait(1200);
    expect(sent).toHaveBeenCalledTimes(2);
  });

  it('sends what was refused when asked to try again', async () => {
    const { model, sent, result } = draw(() => Promise.reject(new SaveRefused('')));

    act(() => model.add('green'));
    await wait(1200);
    await act(() => result.current.flush());

    expect(sent).toHaveBeenCalledTimes(2);
  });
});

describe('why a save failed', () => {
  const answer = (status: number, body?: unknown) =>
    new Response(body === undefined ? null : JSON.stringify(body), { status });

  it('tries again what never reached a server that could answer', async () => {
    expect(await notSaved(answer(503))).not.toBeInstanceOf(SaveRefused);
  });

  it('says to sign in again when the session is gone', async () => {
    for (const status of [401, 419]) {
      const why = await notSaved(answer(status));
      expect(why).toBeInstanceOf(SaveRefused);
      expect(why.message).toBe('You were signed out. Sign in again.');
    }
  });

  it("says the server's own words when it refuses what was drawn", async () => {
    const why = await notSaved(
      answer(422, { errors: { 'items.3.floor': ['The floor must be between -5 and 100.'] } }),
    );

    expect(why).toBeInstanceOf(SaveRefused);
    expect(why.message).toBe('The floor must be between -5 and 100.');
  });

  it('refuses anything else without trying again', async () => {
    const why = await notSaved(answer(500));

    expect(why).toBeInstanceOf(SaveRefused);
    expect(why.message).toBe('');
  });
});
