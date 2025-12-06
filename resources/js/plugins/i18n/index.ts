import yaml from 'js-yaml';

/**
 * Match placeholders, e.g. {my placeholder} and replace them with their
 * translations in the current locale
 */
const replacedSfc = (code: string): string => {
  const codeBits: string[] = [`const dicts = {}`];
  const blocksRegex = new RegExp(`\\bimport block(\\d+) .+\\blocale=([\\w_-]+)\\b.+`, 'g');

  let match;
  while ((match = blocksRegex.exec(code))) {
    codeBits.push(`dicts['${match[2]}'] = block${match[1]}._`);
  }

  if (codeBits.length === 1) {
    return code;
  }

  codeBits.push(`const locale = document.documentElement.lang?.replace('-', '_') ?? 'en'`);
  codeBits.push(`const _ = dicts[locale]`);
  // console.log('BITS', codeBits);
  codeBits.push(code.replace(/"\s*{([^}]+?)}\s*"/g, `_("$1") ?? "$1"`));

  // console.log('CODE', code);
  return codeBits.join(';\n');
};

const i18nPlugin = () => ({
  name: 'i18n',

  transform(code: string, id: string) {
    if (isFacadeRequest(id) && hasI18nBlock(code)) {
      return replacedSfc(code);
    }

    if (!isI18nRequest(id)) {
      return;
    }

    if (!isYamlBlock(id)) {
      return `export default null`;
    }

    const dict = JSON.stringify(yaml.load(code.trim()));

    // TODO : create a directive v-i18n to handle in-template translations
    // @see [VueSchool] Vue.js 3 Custom Directives
    // e.g. <p v-i18n="'my text to translate'"></p>
    // e.g. <p v-i18n:es="'my text to translate'"></p>
    // e.g. <p v-i18n:es="translatableVariable"></p>
    // e.g. <p v-i18n>my text to translate</p>
    // e.g. <p v-i18n:es>my text to translate</p>
    // e.g. <p v-i18n:es>{ translatableVariable }</p>
    //
    // Ideally, we would use the = form for variables, and the innerHTML
    // form for literal strings. That way, variables retain their reactivity

    // TODO : extract this logic to an exportable function in another file
    // Then use a cascade to try:
    //  1. If there's an i18n block, try to find the key there
    //  2. If there's no i18n block, or if the key is not there, fallback to
    //     the external function, that will work as useTranslations' _()
    //  3. If the key is not found there, then use the original string
    // The useTranslations composable should then be part of this package,
    // but it can still be used normally, without installing the plugin
    return `
        const dict = Object.fromEntries(
          Object.entries(${dict})
            .map(([k, v]) => [k.trim(), v.trim()])
        );

        export default {
            _: t => dict[t.trim()]?.trim() ?? t
        }
    `;
  },
});

const isFacadeRequest = (id: string) => /\.vue$/.test(id);
const isI18nRequest = (id: string) => /\bvue&type=i18n\b/.test(id);
const hasI18nBlock = (code: string) => /\bvue&type=i18n\b/.test(code);
const isYamlBlock = (id: string) => /\blang\.ya?ml\b$/.test(id);

export default i18nPlugin;
