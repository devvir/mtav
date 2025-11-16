export type CommonAction = 'click' | 'enter' | 'space' | 'programmatic' | 'disabled' | '';
export type OpenAction = CommonAction;
export type CloseAction = CommonAction | 'click-outside' | 'escape';
