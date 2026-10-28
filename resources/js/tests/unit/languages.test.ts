/**
 * Every language has every word, none left blank, each with the blanks its English has
 * (en.json is empty on purpose: the English is the key).
 */
const LANGUAGES = Object.entries(
  import.meta.glob<Record<string, string>>('/lang/*.json', { eager: true, import: 'default' }),
).filter(([path]) => !path.endsWith('/en.json'));

const blanks = (text: string) => (text.match(/\{\w+\}/g) ?? []).sort();

describe('the languages', () => {
  const [[, reference]] = LANGUAGES;

  it.each(LANGUAGES)('%s has the same words as every other language', (_path, words) => {
    expect(Object.keys(words).sort()).toEqual(Object.keys(reference).sort());
  });

  it.each(LANGUAGES)('%s leaves no word blank, and keeps each one’s blanks', (_path, words) => {
    for (const [english, translated] of Object.entries(words)) {
      expect(translated.trim(), english).not.toBe('');
      expect(blanks(translated), english).toEqual(blanks(english));
    }
  });
});
