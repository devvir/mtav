import type { HttpRequestConfig } from '@inertiajs/core';
import { renderHook } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const { reload, refreshChat, requests, connector, publicChannel, page } = vi.hoisted(() => {
  const changes: ((status: string) => void)[] = [];
  return {
    reload: vi.fn(),
    refreshChat: vi.fn(),
    requests: [] as ((config: HttpRequestConfig) => HttpRequestConfig)[],
    publicChannel: { listen: vi.fn() },
    page: { props: { project: { id: 1 }, auth: { user: { id: 5 } } } },
    /** The websocket's connection, as the test moves it */
    connector: {
      status: 'connecting',
      socket: undefined as string | undefined,
      connectionStatus() {
        return this.status;
      },
      onConnectionChange: (change: (status: string) => void) => {
        changes.push(change);
        return () => undefined;
      },
      socketId() {
        return this.socket;
      },
      becomes(status: string) {
        this.status = status;
        changes.forEach((change) => change(status));
      },
      reset() {
        changes.length = 0;
        this.status = 'connecting';
        this.socket = undefined;
      },
    },
  };
});

const channel = { listen: vi.fn(), stopListening: vi.fn() };
vi.mock('@inertiajs/react', () => ({
  router: { reload },
  usePage: () => page,
  http: {
    onRequest: (handler: (config: HttpRequestConfig) => HttpRequestConfig) =>
      requests.push(handler),
  },
}));
vi.mock('@laravel/echo-react', () => ({
  configureEcho: vi.fn(),
  echo: () => ({
    connector,
    private: () => channel,
    channel: () => publicChannel,
    leaveChannel: vi.fn(),
  }),
}));
vi.mock('@/lib/toasts', () => ({ toast: vi.fn() }));
vi.mock('@/lib/chat', () => ({
  chatState: () => ({}),
  openChat: vi.fn(),
  refreshChat,
  useChatProject: () => ({}),
}));

/** A fresh page: the module as it loads, connected to a websocket server */
const load = async () => {
  vi.resetModules();
  const realtime = await import('@/lib/realtime');
  realtime.connectRealtime({ key: 'k', host: 'h', port: 1, scheme: 'https' });
  return realtime;
};

describe('the connection to the websocket server', { timeout: 30_000 }, () => {
  beforeEach(() => {
    connector.reset();
    requests.length = 0;
    channel.listen.mockReturnValue(channel);
    channel.stopListening.mockReturnValue(channel);
  });

  it('asks again for the page and its counts once it is back after a gap: what was said meanwhile never came', async () => {
    const { listenOn } = await load();
    listenOn('projects.1', { '.changed': vi.fn() });

    connector.becomes('connected');
    expect(reload).not.toHaveBeenCalled();

    connector.becomes('connecting');
    connector.becomes('failed');
    connector.becomes('connected');

    expect(reload).toHaveBeenCalledTimes(1);
    expect(reload).toHaveBeenCalledWith();
    expect(refreshChat).toHaveBeenCalledTimes(1);
  });

  it("names the page's connection on each request, so what it changes is not announced back to it", async () => {
    const { listenOn } = await load();
    listenOn('projects.1', { '.changed': vi.fn() });
    const headers = () =>
      requests.reduce((config, handler) => handler(config), {
        method: 'post',
        url: '/families',
        headers: { Accept: 'text/html' },
      } as HttpRequestConfig).headers;

    expect(headers()).toEqual({ Accept: 'text/html' });

    connector.socket = '1234.5678';
    expect(headers()).toEqual({ Accept: 'text/html', 'X-Socket-ID': '1234.5678' });
  });

  it("reloads what changed, the person's own change from another tab or device included", async () => {
    const { useProjectChanges } = await load();
    renderHook(() => useProjectChanges({ families: ['families'] }));
    const onChange = channel.listen.mock.calls.find(([event]) => event === '.changed')![1];

    onChange({ resource: 'families', action: 'updated', id: 3, by: 5 });

    expect(reload).toHaveBeenCalledWith({ only: ['families'] });
  });
});

describe("an open lottery's page", { timeout: 30_000 }, () => {
  beforeEach(() => {
    vi.useFakeTimers();
    connector.reset();
  });

  afterEach(() => vi.useRealTimers());

  it('asks the server now and then only while it cannot be told', async () => {
    const { useOpenLotteryChanges, OPEN_LOTTERY_EVERY_MS } = await load();
    connector.becomes('connected');
    renderHook(() => useOpenLotteryChanges('grand-lottery'));

    vi.advanceTimersByTime(OPEN_LOTTERY_EVERY_MS * 3);
    expect(reload).not.toHaveBeenCalled();

    connector.becomes('failed');
    vi.advanceTimersByTime(OPEN_LOTTERY_EVERY_MS * 2);
    expect(reload).toHaveBeenCalledTimes(2);

    // Back: once for what it missed, and no more asking
    connector.becomes('connected');
    vi.advanceTimersByTime(OPEN_LOTTERY_EVERY_MS * 3);
    expect(reload).toHaveBeenCalledTimes(3);
  });

  it('asks now and then on a server with no live updates', async () => {
    vi.resetModules();
    const { useOpenLotteryChanges, OPEN_LOTTERY_EVERY_MS } = await import('@/lib/realtime');
    renderHook(() => useOpenLotteryChanges('grand-lottery'));

    vi.advanceTimersByTime(OPEN_LOTTERY_EVERY_MS * 2);

    expect(reload).toHaveBeenCalledTimes(2);
  });
});
