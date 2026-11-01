import { sendJson } from '@/lib/json';
import { afterEach, describe, expect, it, vi } from 'vitest';

describe('a JSON request', () => {
  afterEach(() => vi.unstubAllGlobals());

  it('answers as the server did', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn(async () => new Response('{}', { status: 422 })),
    );

    expect((await sendJson('POST', '/somewhere', {})).status).toBe(422);
  });

  it('never rejects: with no connection, it is a failed answer like any other', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn(async () => Promise.reject(new TypeError('Failed to fetch'))),
    );

    const response = await sendJson('POST', '/somewhere', {});

    expect(response.ok).toBe(false);
    expect(response.status).toBe(503);
  });
});
