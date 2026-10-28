import { useState } from 'react';

const asked = (param: string) => new URLSearchParams(window.location.search).has(param);

/**
 * A dialog the address can open (?new=1, as links to "add" do): open on arrival when asked
 * and allowed, and leaving the address clean once closed.
 */
export function useAddressedDialog(param: string, allowed = true) {
  const [opened, setOpened] = useState(() => allowed && asked(param));

  const close = () => {
    setOpened(false);
    if (asked(param)) {
      const url = new URL(window.location.href);
      url.searchParams.delete(param);
      window.history.replaceState(window.history.state, '', url);
    }
  };

  return { opened, open: () => setOpened(true), close };
}
