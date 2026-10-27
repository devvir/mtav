type Replacements = Record<string, string | number>;

// Import supported locales explicitly
import en from '@lang/en.json';
import esUY from '@lang/es_UY.json';

const locales = {
  es_UY: esUY,
  en: en,
};

export const useTranslation = () => {
  const locale = ref<string>(document.documentElement.lang || 'en');
  const translations = ref<Record<string, string>>({});

  const setLocale = async (newLocale: string): Promise<void> => {
    newLocale = newLocale.replace('-', '_');

    const newTranslations = locales[newLocale as keyof typeof locales] || locales.en;

    if (!newTranslations) {
      return console.warn(`Locale '${newLocale}' not found, falling back to English.`);
    }

    locale.value = newLocale;
    translations.value = newTranslations;
  };

  const trans = (key: string, replacements: Replacements = {}): string => {
    const dict = translations.value ?? { [key]: key };

    const translatedString = dict[key] || key;

    return Object.entries(replacements).reduce((result, [placeholder, value]) => {
      const regex = new RegExp(`\\{${placeholder}\\}`, 'g');
      return result.replace(regex, String(value));
    }, translatedString);
  };

  setLocale(locale.value);

  return { locale, translations, setLocale, trans, _: trans };
};

export const { locale, translations, setLocale, trans, _ } = useTranslation();

/** The locale as browsers name it (es_UY → es-UY), for Intl and toLocale* formatting */
export const languageTag = computed(() => locale.value.replace('_', '-'));

/**
 * A count with its noun in the right form: `_n('{count} family', '{count} families', n)`.
 * Each form is translated on its own; the language's plural rules pick one.
 */
export function _n(one: string, other: string, count: number, replacements: Replacements = {}) {
  const form = new Intl.PluralRules(languageTag.value).select(count) === 'one' ? one : other;

  return trans(form, { count, ...replacements });
}
