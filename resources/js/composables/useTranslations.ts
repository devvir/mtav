type Replacements = Record<string, string>;

export const useTranslation = () => {
  const locale = ref<string>(document.documentElement.lang || 'en');
  const translations = ref<Record<string, string>>({});

  const setLocale = async (newLocale: string): Promise<void> => {
    newLocale = newLocale.replace('-', '_');

    const newTranslations: Record<string, string> = (
      await import(`../../../lang/${newLocale}.json`)
    )?.default;

    if (!newTranslations) {
      return console.warn(`Locale '${newLocale}' not found.`);
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
