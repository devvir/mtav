import {
  MantineProvider,
  type MantineColorScheme,
  type MantineColorSchemeManager,
} from '@mantine/core';
import { createContext, useContext, useMemo, useState, type ReactNode } from 'react';
import { paletteOf } from './palettes';
import { themeFor, tokensFor } from './theme';

/**
 * A person's appearance: a palette, a colour scheme (light, dark, or the device's) and
 * whether contrast is high. Kept in cookies, so the page is drawn right before any script
 * runs (resources/views/app.blade.php reads them too). Changing one redraws at once.
 */
const YEAR = 365 * 24 * 60 * 60;

const readCookie = (name: string): string | null =>
  document.cookie.match(new RegExp(`(?:^|; )${name}=([^;]*)`))?.[1] ?? null;

const writeCookie = (name: string, value: string) => {
  document.cookie = `${name}=${value};path=/;max-age=${YEAR};SameSite=Lax`;
};

/** The colour scheme, kept in the `scheme` cookie */
const schemes: MantineColorSchemeManager = {
  get: (fallback) => (readCookie('scheme') as MantineColorScheme | null) ?? fallback,
  set: (value) => writeCookie('scheme', value),
  subscribe: () => undefined,
  unsubscribe: () => undefined,
  clear: () => writeCookie('scheme', 'auto'),
};

interface AppearanceContext {
  palette: string;
  setPalette: (id: string) => void;
  highContrast: boolean;
  setHighContrast: (on: boolean) => void;
}

const Context = createContext<AppearanceContext>({
  palette: 'terracotta',
  setPalette: () => undefined,
  highContrast: false,
  setHighContrast: () => undefined,
});

/** The palette and contrast, and how to change them (the scheme: Mantine's useMantineColorScheme) */
export const useAppearance = () => useContext(Context);

/** `closeLabel`: what a dialog's close button is called, in the app's language */
export function Appearance({
  closeLabel = 'Close',
  children,
}: {
  closeLabel?: string;
  children: ReactNode;
}) {
  const [palette, setPaletteState] = useState(() => paletteOf(readCookie('palette')).id);
  const [highContrast, setHighContrastState] = useState(() => readCookie('contrast') === 'high');

  const chosen = paletteOf(palette);
  const theme = useMemo(() => themeFor(chosen, { close: closeLabel }), [chosen, closeLabel]);
  const tokens = useMemo(() => tokensFor(chosen, highContrast), [chosen, highContrast]);

  const setPalette = (id: string) => {
    setPaletteState(id);
    writeCookie('palette', id);
  };

  const setHighContrast = (on: boolean) => {
    setHighContrastState(on);
    writeCookie('contrast', on ? 'high' : 'standard');
    document.documentElement.toggleAttribute('data-high-contrast', on);
  };

  return (
    <Context.Provider value={{ palette, setPalette, highContrast, setHighContrast }}>
      <MantineProvider
        theme={theme}
        cssVariablesResolver={tokens}
        colorSchemeManager={schemes}
        defaultColorScheme="auto"
      >
        {children}
      </MantineProvider>
    </Context.Provider>
  );
}
