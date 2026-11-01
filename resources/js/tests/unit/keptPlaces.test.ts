import { keptPlaces } from '@/layouts/ProjectLayout';

vi.mock('@/lib/route', () => ({
  route: (name: string) => name,
  routeIs: () => false,
}));

describe('the places kept on the device', () => {
  it('are a project’s every place, its actions apart', () => {
    expect(keptPlaces(1)).toEqual(
      expect.arrayContaining(['lottery.show', 'events.index', 'media.index', 'forum.index']),
    );
    expect(keptPlaces(1)).not.toContain('invitations.index');
  });

  it('leave out what the project turned off', () => {
    const kept = keptPlaces(1, { forum: false, events: false, gallery: false, files: false });

    expect(kept).not.toContain('forum.index');
    expect(kept).not.toContain('events.index');
    expect(kept).not.toContain('media.index');
    expect(kept).toContain('lottery.show');
  });

  it('keep the media page while either of its kinds is on', () => {
    expect(keptPlaces(1, { gallery: false })).toContain('media.index');
    expect(keptPlaces(1, { files: false })).toContain('media.index');
  });
});
