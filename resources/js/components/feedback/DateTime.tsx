import { languageTag } from '@/lib/translations';

const UNITS: [Intl.RelativeTimeFormatUnit, number][] = [
  ['year', 31536000],
  ['month', 2592000],
  ['week', 604800],
  ['day', 86400],
  ['hour', 3600],
  ['minute', 60],
];

/** A date (and time) as people read it in the app's language: "27 oct. 2026, 10:00" */
export const formatDate = (value: string, time = true) =>
  new Intl.DateTimeFormat(languageTag(), {
    dateStyle: 'medium',
    ...(time ? { timeStyle: 'short' } : {}),
  }).format(new Date(value));

/** How far a moment is from now: "in 3 days", "yesterday" */
export function formatRelative(value: string) {
  const seconds = (new Date(value).getTime() - Date.now()) / 1000;
  const [unit, size] = UNITS.find(([, span]) => Math.abs(seconds) >= span) ?? ['second', 1];

  return new Intl.RelativeTimeFormat(languageTag(), { numeric: 'auto' }).format(
    Math.round(seconds / size),
    unit,
  );
}

/** A date (and time), formatted for the reader's language; the full timestamp on hover */
export function DateTime({
  value,
  time = true,
  relative,
}: {
  value: string | null;
  time?: boolean;
  relative?: boolean;
}) {
  if (!value) return null;

  return (
    <time dateTime={value} title={new Date(value).toLocaleString(languageTag())}>
      {relative ? formatRelative(value) : formatDate(value, time)}
    </time>
  );
}
