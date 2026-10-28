import en from '@lang/en.json';
import esUY from '@lang/es_UY.json';

/**
 * The app's words: `_('English text')` everywhere, the other languages keyed by the English
 * (lang/es_UY.json; en.json is empty on purpose). The locale is set once, when the app
 * starts, from the platform's.
 */
type Replacements = Record<string, string | number>;

const LOCALES: Record<string, Record<string, string>> = { en, es_UY: esUY };

let locale = 'en';
let words: Record<string, string> = {};

export function setLocale(next: string) {
  locale = next.replace('-', '_');
  words = LOCALES[locale] ?? {};
}

export const getLocale = () => locale;

/** The locale as browsers name it (es_UY → es-UY), for Intl and toLocale* formatting */
export const languageTag = () => locale.replace('_', '-');

const fill = (text: string, replacements: Replacements) =>
  Object.entries(replacements).reduce(
    (result, [name, value]) => result.replaceAll(`{${name}}`, String(value)),
    text,
  );

export const _ = (key: string, replacements: Replacements = {}): string =>
  fill(words[key] || key, replacements);

/**
 * A count with its noun in the right form: `_n('{count} family', '{count} families', n)`.
 * Each form is translated on its own; the language's plural rules pick one.
 */
export function _n(one: string, other: string, count: number, replacements: Replacements = {}) {
  const form = new Intl.PluralRules(languageTag()).select(count) === 'one' ? one : other;

  return _(form, { count, ...replacements });
}
