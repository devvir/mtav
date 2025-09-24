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

  codeBits.push(`const locale = document.documentElement.lang ?? 'en'`);
  codeBits.push(`const _ = dicts[locale]`);
  codeBits.push(code.replace(/"\s*{([^}]+?)}\s*"/g, `_("$1") ?? "$1"`));

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
