export const MAYBEMODAL = Symbol('MaybeModal');

export type Modal = {
  id: string;
  config: {
    closeExplicitly: boolean;
  };
};

export const preventFormClosure = (modal?: Modal, onCloseAttempt?: (src: string) => void) => {
  if (!modal) {
    return;
  }

  modal.config.closeExplicitly = true;

  const selector = `[data-inertiaui-modal-id="${modal.id}"] .im-close-button`;
  const closeBtn = document.querySelector(selector);

  if (!closeBtn) {
    return;
  }

  closeBtn?.replaceWith(closeBtn.cloneNode(true));

  const newCloseBtn = document.querySelector(selector) as HTMLButtonElement;

  newCloseBtn.addEventListener('click', () => {
    // TODO : implement confirmation popup on Esc / click outside / close button
    onCloseAttempt?.('button');
  });
};
