import { initials } from '@/design/components/PersonAvatar';

describe("a person's initials", () => {
  it('are the first letters of their first two words', () => {
    expect(initials('ana maría García')).toBe('AM');
    expect(initials('  Bruno  ')).toBe('B');
  });

  it('take a first character whole: an emoji, a letter with its accent typed apart', () => {
    expect(initials('🌻 Flores')).toBe('🌻F');
    expect(initials('👩‍👩‍👧 Familia')).toBe('👩‍👩‍👧F');
    expect(initials('Élise Martin')).toBe('ÉM');
  });
});
