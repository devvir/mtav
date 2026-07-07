import { describe, expect, it } from 'vitest';

/**
 * Verifies the jsdom environment mocks provided by tests/setup.ts.
 */
describe('Vitest Setup Verification', () => {
  it('localStorage is mocked', () => {
    window.localStorage.setItem('test-key', 'test-value');
    expect(window.localStorage.getItem('test-key')).toBe('test-value');
    window.localStorage.clear();
  });

  it('matchMedia is mocked', () => {
    const mq = window.matchMedia('(max-width: 768px)');
    expect(mq).toBeDefined();
    expect(mq.matches).toBe(false);
  });
});
