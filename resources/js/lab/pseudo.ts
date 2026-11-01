/**
 * States a pointer or a keyboard puts a component in, held still to be seen
 * side by side: every style rule that answers to `:hover`, `:active` or focus
 * gets a twin that answers to a class instead (`.ps-hover`, `.ps-active`,
 * `.ps-focus`, put on one element by `Held`), right after it, so the cascade
 * is the same. As with a real pointer, hovering or pressing an element holds
 * its ancestors too; focus holds the element, and `:focus-within` its
 * ancestors. The components and their styles are untouched; only the lab's
 * page is.
 */

const STATES: [RegExp, string][] = [
  [/:hover(?![-\w])/g, ':is(.ps-hover, :has(.ps-hover))'],
  [/:active(?![-\w])/g, ':is(.ps-active, :has(.ps-active))'],
  [/:focus-visible(?![-\w])/g, '.ps-focus'],
  [/:focus-within(?![-\w])/g, ':is(.ps-focus, :has(.ps-focus))'],
  [/:focus(?![-\w])/g, '.ps-focus'],
];

/** The twin of a selector, or nothing when it has no state to hold. */
const twin = (selector: string): string | null => {
  let held = selector;
  for (const [state, held_] of STATES) held = held.replace(state, held_);
  return held === selector ? null : held;
};

let done = new WeakSet<CSSStyleSheet>();
/** What was put in, to take out on leaving */
let added: { list: CSSStyleSheet | CSSGroupingRule; rule: CSSRule }[] = [];

const insert = (list: CSSStyleSheet | CSSGroupingRule, text: string, at: number) => {
  try {
    list.insertRule(text, at);
    added.push({ list, rule: list.cssRules[at]! });
  } catch {
    /* a selector this browser cannot parse */
  }
};

/**
 * Every rule of a list (a sheet, or a media or layer block in it), twinned
 * where it answers to a state. A rule kept for devices that hover (`@media
 * (hover: hover)`) has its twin put just after the block, outside it: a
 * state held still is shown on any device, a touch screen's too.
 */
const twinRules = (list: CSSStyleSheet | CSSGroupingRule, outside: string[] = []) => {
  for (let at = list.cssRules.length - 1; at >= 0; at -= 1) {
    const rule = list.cssRules[at];
    if (rule instanceof CSSStyleRule) {
      const selector = twin(rule.selectorText);
      if (selector) {
        const text = `${selector} { ${rule.style.cssText} }`;
        if (list instanceof CSSMediaRule && /hover/.test(list.conditionText)) outside.unshift(text);
        else insert(list, text, at + 1);
      }
    } else if (rule instanceof CSSGroupingRule) {
      const lifted: string[] = [];
      twinRules(rule, lifted);
      for (const text of lifted.reverse()) insert(list, text, at + 1);
    }
  }
};

const twinSheets = () => {
  for (const sheet of [...document.styleSheets]) {
    if (done.has(sheet)) continue;
    done.add(sheet);
    try {
      twinRules(sheet);
    } catch {
      /* a sheet from elsewhere, not ours to read */
    }
  }
};

/**
 * Holds states from now on, for the sheets there now and those that arrive (an edit, reloaded
 * in place), until what it returns is called: everything goes back as it was, for the rest of
 * the app.
 */
export const holdStates = () => {
  twinSheets();
  const arriving = new MutationObserver(() => twinSheets());
  arriving.observe(document.head, { childList: true, subtree: true, characterData: true });

  return () => {
    arriving.disconnect();
    for (const { list, rule } of added) {
      const at = [...list.cssRules].indexOf(rule);
      if (at >= 0) list.deleteRule(at);
    }
    added = [];
    done = new WeakSet();
  };
};
