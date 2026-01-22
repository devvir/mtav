// Copilot - Pending review
import { describe, expect, it, vi } from 'vitest';

vi.unmock('@/composables/useInitials');

import { getInitials, useInitials } from '@/composables/useInitials';

describe('useInitials composable', () => {
  const { getInitials: getInitialsFromComposable } = useInitials();

  describe('getInitials function (direct call)', () => {
    it('returns initials for full name with first and last', () => {
      expect(getInitials('John Doe')).toBe('JD');
    });

    it('returns initials with first and last name from multi-word names', () => {
      expect(getInitials('Jean Claude Van Damme')).toBe('JD');
    });

    it('returns single letter for single-word name', () => {
      expect(getInitials('Madonna')).toBe('M');
    });

    it('returns empty string for empty name', () => {
      expect(getInitials('')).toBe('');
    });

    it('returns empty string for undefined name', () => {
      expect(getInitials(undefined)).toBe('');
    });

    it('returns empty string for null name', () => {
      expect(getInitials(null as any)).toBe('');
    });

    it('handles names with leading/trailing whitespace', () => {
      expect(getInitials('  John Doe  ')).toBe('JD');
    });

    it('handles names with multiple spaces between words', () => {
      expect(getInitials('John    Doe')).toBe('JD');
    });

    it('uppercases initials', () => {
      expect(getInitials('john doe')).toBe('JD');
      expect(getInitials('JOHN DOE')).toBe('JD');
      expect(getInitials('jOhN dOe')).toBe('JD');
    });

    it('handles special characters in names', () => {
      expect(getInitials('José María')).toBe('JM');
      expect(getInitials('François Müller')).toBe('FM');
    });

    it('handles single letter names', () => {
      expect(getInitials('A B')).toBe('AB');
    });

    it('returns first and last letter for two-word name', () => {
      expect(getInitials('Alice Bob')).toBe('AB');
    });

    it('returns first and last letter for three-word name', () => {
      expect(getInitials('Alice Bob Charlie')).toBe('AC');
    });
  });

  describe('getInitials from composable', () => {
    it('returns same result as direct function call', () => {
      const testName = 'John Doe';
      expect(getInitialsFromComposable(testName)).toBe(getInitials(testName));
    });

    it('returns empty string for empty name', () => {
      expect(getInitialsFromComposable('')).toBe('');
    });
  });
});
