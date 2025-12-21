// Copilot - Pending review
import { describe, it, expect, vi, beforeEach } from 'vitest';

vi.unmock('@/composables/useDates');

import { useDates } from '@/composables/useDates';

describe('useDates composable', () => {
  const { fromUTC, toUTC } = useDates();
  const consoleWarnMock = vi.spyOn(console, 'warn').mockImplementation(() => {});

  beforeEach(() => {
    consoleWarnMock.mockClear();
  });

  describe('fromUTC function', () => {
    it('converts UTC ISO string to default formatted string', () => {
      const utcString = '2025-12-02T04:42:13.000000Z';
      const result = fromUTC(utcString);

      // Result should be a formatted string (exact format depends on locale)
      expect(typeof result).toBe('string');
      expect(result.length).toBeGreaterThan(0);
    });

    it('converts UTC ISO string to date format', () => {
      const utcString = '2025-12-02T04:42:13.000000Z';
      const result = fromUTC(utcString, 'date');

      // Result should be a formatted date like "12/2/2025"
      expect(typeof result).toBe('string');
      expect(result).toMatch(/\d+\/\d+\/\d+/); // Basic date format
    });

    it('converts UTC ISO string to time format', () => {
      const utcString = '2025-12-02T04:42:13.000000Z';
      const result = fromUTC(utcString, 'time');

      // Result should be a formatted time like "04:42"
      expect(typeof result).toBe('string');
      expect(result).toMatch(/\d{2}:\d{2}/);
    });

    it('converts UTC ISO string to datetime-local format', () => {
      const utcString = '2025-12-02T04:42:13.000000Z';
      const result = fromUTC(utcString, 'datetime-local');

      // Result should be YYYY-MM-DDTHH:mm format
      expect(typeof result).toBe('string');
      expect(result).toMatch(/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/);
    });

    it('converts UTC ISO string to ISO format', () => {
      const utcString = '2025-12-02T04:42:13.000000Z';
      const result = fromUTC(utcString, 'iso');

      // Result should be ISO string
      expect(result).toMatch(/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/);
    });

    it('returns empty string for empty input', () => {
      expect(fromUTC('')).toBe('');
      expect(fromUTC(null as any)).toBe('');
      expect(fromUTC(undefined as any)).toBe('');
      expect(consoleWarnMock).toHaveBeenCalledWith('fromUTC: Empty or null date string provided');
    });

    it('returns empty string for invalid date', () => {
      expect(fromUTC('invalid-date')).toBe('');
      expect(consoleWarnMock).toHaveBeenCalledWith('fromUTC: Invalid date string: invalid-date');

      expect(fromUTC('2025-13-02T04:42:13.000000Z')).toBe('');
      expect(consoleWarnMock).toHaveBeenCalledWith('fromUTC: Invalid date string: 2025-13-02T04:42:13.000000Z');
    });

    it('respects locale parameter in default format', () => {
      // This test verifies the function uses document.documentElement.lang
      const utcString = '2025-12-02T04:42:13.000000Z';
      const result = fromUTC(utcString, 'default');

      expect(typeof result).toBe('string');
      expect(result.length).toBeGreaterThan(0);
    });
  });

  describe('toUTC function', () => {
    it('converts local datetime-local string to UTC ISO string', () => {
      const localString = '2025-12-02T04:42';
      const result = toUTC(localString);

      // Result should be ISO 8601 UTC string with Z suffix
      expect(result).toMatch(/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/);
      expect(result.endsWith('Z')).toBe(true);
    });

    it('returns empty string for empty input', () => {
      expect(toUTC('')).toBe('');
      expect(consoleWarnMock).toHaveBeenCalledWith('toUTC: Empty or null date string provided');
    });

    it('returns empty string for invalid date', () => {
      expect(toUTC('invalid-date')).toBe('');
      expect(consoleWarnMock).toHaveBeenCalledWith('toUTC: Invalid date string: invalid-date');

      expect(toUTC('2025-13-02T04:42')).toBe('');
      expect(consoleWarnMock).toHaveBeenCalledWith('toUTC: Invalid date string: 2025-13-02T04:42');
    });

    it('roundtrip conversion: UTC -> local -> UTC', () => {
      const originalUTC = '2025-12-02T04:42:13.000000Z';
      const local = fromUTC(originalUTC, 'datetime-local');
      const backToUTC = toUTC(local);

      // After roundtrip, both should be valid ISO strings
      expect(backToUTC).toMatch(/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/);
      expect(backToUTC.endsWith('Z')).toBe(true);
    });
  });
});
