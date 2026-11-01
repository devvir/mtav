import { listenOn } from '@/lib/realtime';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const { channel, leaveChannel } = vi.hoisted(() => ({
  channel: { listen: vi.fn(), stopListening: vi.fn() },
  leaveChannel: vi.fn(),
}));
vi.mock('@inertiajs/react', () => ({ router: {}, usePage: vi.fn() }));
vi.mock('@laravel/echo-react', () => ({
  configureEcho: vi.fn(),
  echo: () => ({ private: () => channel, leaveChannel }),
}));

describe('listening on a shared channel', () => {
  beforeEach(() => {
    channel.listen.mockReturnValue(channel);
    channel.stopListening.mockReturnValue(channel);
  });

  it('stops only the handler that stops, others on the same event going on', () => {
    const page = vi.fn();
    const tools = vi.fn();
    const stopPage = listenOn('projects.1', { '.changed': page });
    const stopTools = listenOn('projects.1', { '.changed': tools });

    stopTools();

    expect(channel.stopListening).toHaveBeenCalledTimes(1);
    expect(channel.stopListening).toHaveBeenCalledWith('.changed', tools);
    expect(leaveChannel).not.toHaveBeenCalled();

    stopPage();
  });

  it('leaves the channel with the last one to stop, and only then', () => {
    const stopOne = listenOn('users.1', { '.message': vi.fn(), '.message-removed': vi.fn() });
    const stopOther = listenOn('users.1', { '.notification': vi.fn() });

    stopOne();
    expect(leaveChannel).not.toHaveBeenCalled();

    stopOther();
    expect(leaveChannel).toHaveBeenCalledTimes(1);
    expect(leaveChannel).toHaveBeenCalledWith('private-users.1');
  });

  it('counts each channel apart', () => {
    const stopOne = listenOn('projects.1', { '.changed': vi.fn() });
    const stopTwo = listenOn('projects.2', { '.changed': vi.fn() });

    stopOne();
    expect(leaveChannel).toHaveBeenCalledWith('private-projects.1');
    expect(leaveChannel).not.toHaveBeenCalledWith('private-projects.2');

    stopTwo();
  });
});
