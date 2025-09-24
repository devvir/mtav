import esTranslations from '../../../lang/es.json';

const translations = {
  es: esTranslations,
} as Record<string, Record<string, string>>;

export const useTranslation = () => {
  const locale = ref<string>(document.documentElement.lang);

  const setLocale = (newLocale: string): void => {
    if (!translations[newLocale]) {
      return console.warn(`Locale '${newLocale}' not found.`);
    }

    locale.value = newLocale;
  };

  const trans = (key: string): string => {
    const dict = translations[locale.value] ?? { [key]: key };
    return dict[key] || key;
  };

  return { locale, setLocale, trans, _: trans };
};

export const { locale, setLocale, trans, _ } = useTranslation();
