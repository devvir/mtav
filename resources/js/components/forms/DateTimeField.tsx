import { getLocale } from '@/lib/translations';
import { DatesProvider, DateTimePicker, type DateTimePickerProps } from '@mantine/dates';
import { IconCalendarEvent } from '@tabler/icons-react';
import 'dayjs/locale/es';
import 'dayjs/locale/fr';
import 'dayjs/locale/it';
import 'dayjs/locale/pt-br';

/** The calendar's language for each of the app's */
const CALENDAR: Record<string, string> = { es_UY: 'es', fr: 'fr', it: 'it', pt_BR: 'pt-br' };

/** `2026-11-05T18:30` (what the server takes) and the picker's `2026-11-05 18:30:00` */
const toPicker = (value: string) => (value ? `${value.replace('T', ' ').slice(0, 16)}:00` : null);
const fromPicker = (value: string | null) => (value ? value.replace(' ', 'T').slice(0, 16) : '');

/**
 * A date and its time, picked on a calendar in the person's language (weeks from Monday,
 * 24-hour time): the same in every browser, unlike the browser's own field. Its value is
 * local time, `YYYY-MM-DDTHH:mm`, or empty.
 */
export function DateTimeField({
  value,
  onChange,
  min,
  ...props
}: {
  value: string;
  onChange: (value: string) => void;
  /** The earliest it may be, in the same form */
  min?: string;
} & Omit<DateTimePickerProps, 'value' | 'onChange' | 'minDate'>) {
  return (
    <DatesProvider settings={{ locale: CALENDAR[getLocale()] ?? 'en', firstDayOfWeek: 1 }}>
      <DateTimePicker
        value={toPicker(value)}
        onChange={(picked) => onChange(fromPicker(picked as string | null))}
        minDate={min ? toPicker(min)!.slice(0, 10) : undefined}
        valueFormat="D MMMM YYYY, HH:mm"
        leftSection={<IconCalendarEvent size={18} />}
        clearable
        dropdownType="popover"
        data-field={props.name}
        {...props}
      />
    </DatesProvider>
  );
}
