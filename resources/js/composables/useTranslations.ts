type Replacements = Record<string, string>;

// Import supported locales explicitly
import esUY from '@lang/es_UY.json';
import en from '@lang/en.json';

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
