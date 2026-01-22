/**
 * Date utilities composable for handling UTC date strings from Laravel.
 *
 * Laravel sends dates in UTC ISO 8601 format (e.g., "2025-12-02T04:42:13.000000Z").
 * This composable provides utilities to convert between UTC and local time,
 * and format dates for display and form inputs.
 */

/**
 * Convert UTC date string from Carbon to local time.
 *
 * @param utcString - ISO 8601 UTC date string from Laravel (e.g., "2025-12-02T04:42:13.000000Z")
 * @param format - Optional format string
 * @returns Formatted date string in local timezone
 */
export function fromUTC(
  utcString: string,
  format: 'default' | 'date' | 'time' | 'datetime-local' | 'iso' = 'default',
): string {
  if (!utcString) {
    console.warn('fromUTC: Empty or null date string provided');
    return '';
  }

  const date = new Date(utcString);

  if (isNaN(date.getTime())) {
    console.warn(`fromUTC: Invalid date string: ${utcString}`);
    return '';
  }

  switch (format) {
    case 'date':
      return new Intl.DateTimeFormat().format(date);

    case 'time':
      return new Intl.DateTimeFormat(undefined, {
        hour: '2-digit',
        minute: '2-digit',
      }).format(date);

    case 'datetime-local':
      // Format: YYYY-MM-DDTHH:mm (for <input type="datetime-local">)
      // toISOString() returns UTC, slice off the 'Z' and seconds
      const offset = date.getTimezoneOffset();
      const localDate = new Date(date.getTime() - offset * 60 * 1000);
      return localDate.toISOString().slice(0, 16);

    case 'iso':
      return date.toISOString();

    case 'default':
    default:
      // Example: "Dec 2, 2025, 11:07 AM" (en-US) or "2 dic 2025, 11:07" (es-UY)
      const locale = document.documentElement.lang || 'en';
      return new Intl.DateTimeFormat(locale, {
        dateStyle: 'medium',
        timeStyle: 'short',
      }).format(date);
  }
}

/**
 * Convert local datetime to UTC string for Laravel.
 *
 * @param localString - Local date string or datetime-local input value (YYYY-MM-DDTHH:mm format)
 * @returns ISO 8601 UTC string for Laravel
 */
export function toUTC(localString: string): string {
  if (!localString) {
    console.warn('toUTC: Empty or null date string provided');
    return '';
  }

  // new Date() interprets the string in the local timezone when no timezone is specified
  // For datetime-local format (YYYY-MM-DDTHH:mm), this is exactly what we want
  const date = new Date(localString);

  if (isNaN(date.getTime())) {
    console.warn(`toUTC: Invalid date string: ${localString}`);
    return '';
  }

  // toISOString() converts to UTC
  return date.toISOString();
}

/**
 * Composable hook for date utilities.
 *
 * @example
 * const { fromUTC, toUTC } = useDates();
 * const created_at = fromUTC(family.created_at);
 * const startDate = fromUTC(event.start_date, 'datetime-local');
 */
export function useDates() {
  return {
    fromUTC,
    toUTC,
  };
}
