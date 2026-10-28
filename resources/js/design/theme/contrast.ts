import type { MantineColorsTuple } from '@mantine/core';

/**
 * Colours chosen by measured contrast (WCAG 2): every text and every filled background the
 * theme draws reads at 4.6:1 or more (a hair above the 4.5:1 of AA), whatever the palette.
 */

const AA = 4.6;

const channels = (hex: string) => [1, 3, 5].map((at) => parseInt(hex.slice(at, at + 2), 16));

const toHex = (rgb: number[]) =>
  `#${rgb.map((v) => Math.round(v).toString(16).padStart(2, '0')).join('')}`;

/** A colour's relative luminance */
export const luminance = (hex: string) => {
  const [r, g, b] = channels(hex).map((v) => {
    const c = v / 255;
    return c <= 0.04045 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4;
  });
  return 0.2126 * r! + 0.7152 * g! + 0.0722 * b!;
};

/** How well one colour reads on another */
export const contrast = (a: string, b: string) => {
  const [hi, lo] = [luminance(a), luminance(b)].sort((x, y) => y - x);
  return (hi! + 0.05) / (lo! + 0.05);
};

/** `a` over `b`, `amount` of the way (0 → b, 1 → a) */
export const mix = (a: string, b: string, amount: number) => {
  const [ca, cb] = [channels(a), channels(b)];
  return toHex(ca.map((v, i) => v * amount + cb[i]! * (1 - amount)));
};

/** The colour, darkened (toward black) or lightened (toward white) until it reads on `ground` */
const pushUntil = (hex: string, ground: string, toward: string) => {
  let colour = hex;
  for (let step = 0.1; contrast(colour, ground) < AA && step <= 1; step += 0.1)
    colour = mix(toward, hex, step);
  return colour;
};

/** The first of these shades that reads on `ground`, or the last one pushed until it does */
const firstReading = (shades: string[], ground: string, toward: string) =>
  shades.find((shade) => contrast(shade, ground) >= AA) ??
  pushUntil(shades.at(-1)!, ground, toward);

export interface SchemeColours {
  filled: string;
  filledHover: string;
  light: string;
  lightHover: string;
  /** Text and lines in the colour: light and subtle buttons, outlines, links, badges */
  text: string;
}

/**
 * A colour's parts in a scheme, for a page drawn on `surface`: a filled background white
 * reads on, the soft tints, and the text that reads on those tints (and on the surface).
 */
export function schemeColours(
  tuple: MantineColorsTuple,
  scheme: 'light' | 'dark',
  surface: string,
): SchemeColours {
  const shades = [...tuple];
  const filled = firstReading(shades.slice(scheme === 'light' ? 6 : 5), '#ffffff', '#000000');
  const filledHover = mix('#000000', filled, 0.12);

  if (scheme === 'light') {
    const [light, lightHover] = [shades[0]!, shades[1]!];
    const text = firstReading(shades.slice(6), lightHover, '#000000');
    return { filled, filledHover, light, lightHover, text };
  }

  const light = mix(shades[5]!, surface, 0.16);
  const lightHover = mix(shades[5]!, surface, 0.26);
  const text = firstReading(shades.slice(0, 5).reverse(), lightHover, '#ffffff');
  return { filled, filledHover, light, lightHover, text };
}

/** Mantine's variables for one colour in one scheme */
export const mantineVariables = (name: string, colours: SchemeColours): Record<string, string> => ({
  [`--mantine-color-${name}-filled`]: colours.filled,
  [`--mantine-color-${name}-filled-hover`]: colours.filledHover,
  [`--mantine-color-${name}-light`]: colours.light,
  [`--mantine-color-${name}-light-hover`]: colours.lightHover,
  [`--mantine-color-${name}-light-color`]: colours.text,
  [`--mantine-color-${name}-outline`]: colours.text,
  [`--mantine-color-${name}-outline-hover`]: colours.light,
  [`--mantine-color-${name}-text`]: colours.text,
});
