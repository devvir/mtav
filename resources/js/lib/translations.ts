/**
 * The app's words: `_('English text')` everywhere, the other languages keyed by the English
 * (lang/<locale>.json; en.json is empty on purpose). The locale is set once, when the app
 * starts (the person's, else the platform's); only that language's words are downloaded.
 */
type Replacements = Record<string, string | number>;
type Words = Record<string, string>;

const LANGUAGES = import.meta.glob<Words>('/lang/*.json', { import: 'default' });

let locale = 'en';
let words: Words = {};

export function setLocale(next: string, dictionary: Words = {}) {
  locale = next.replace('-', '_');
  words = dictionary;
}

/** Switches to a language, once its words are here */
export async function loadLocale(next: string) {
  const name = next.replace('-', '_');
  const load = name === 'en' ? undefined : LANGUAGES[`/lang/${name}.json`];

  setLocale(name, load ? await load() : {});
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
