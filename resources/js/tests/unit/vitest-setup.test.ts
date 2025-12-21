// Copilot - Pending review
import { describe, it, expect } from 'vitest';

describe('Vitest Setup Verification', () => {
  it('vitest is working', () => {
    expect(true).toBe(true);
  });

  it('can do basic math', () => {
    expect(1 + 1).toBe(2);
  });

  it('can do string operations', () => {
    const text = 'hello'.toUpperCase();
    expect(text).toBe('HELLO');
  });

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
