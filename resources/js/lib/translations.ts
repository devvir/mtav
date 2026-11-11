import { createElement, Fragment, type ReactNode } from 'react';

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

/**
 * Switches to a language, once its words are here. When they cannot be fetched, on a bad
 * connection, the app still opens: in English, the words every language is keyed by.
 */
export async function loadLocale(next: string) {
  const name = next.replace('-', '_');
  const load = name === 'en' ? undefined : LANGUAGES[`/lang/${name}.json`];

  try {
    setLocale(name, load ? await load() : {});
  } catch {
    setLocale('en');
  }
}

export const getLocale = () => locale;

/** The locale as browsers name it (es_UY → es-UY), for Intl and toLocale* formatting */
export const languageTag = () => locale.replace('_', '-');

/** Each blank filled once, with the value as written ("A$$B" stays so; a "{name}" in it stays too) */
const fill = (text: string, replacements: Replacements) =>
  text.replace(/\{(\w+)\}/g, (blank, name: string) =>
    Object.hasOwn(replacements, name) ? String(replacements[name]) : blank,
  );

export const _ = (key: string, replacements: Replacements = {}): string =>
  fill(words[key] || key, replacements);

/**
 * A whole sentence with parts of the page in its blanks, which each language places where
 * it needs them: `_nodes('Scheduled for {date}', { date: <DateTime value={date} /> })`.
 */
export const _nodes = (key: string, nodes: Record<string, ReactNode>): ReactNode =>
  (words[key] || key)
    .split(/(\{\w+\})/)
    .map((part, index) =>
      createElement(Fragment, { key: index }, nodes[part.slice(1, -1)] ?? part),
    );

/**
 * A count with its noun in the right form: `_n('{count} family', '{count} families', n)`.
 * Each form is translated on its own; the language's plural rules pick one.
 */
export function _n(one: string, other: string, count: number, replacements: Replacements = {}) {
  const form = new Intl.PluralRules(languageTag()).select(count) === 'one' ? one : other;

  return _(form, { count, ...replacements });
}
