export type ValueType = string | number | boolean | null | undefined;

export interface CommonElementSpecs {
  label: string;
  labelPrefix?: string;
  required?: boolean;
  disabled?: boolean;
}

export interface HiddenInputSpecs {
  element: 'input';
  type: 'hidden';
  value?: string | number | boolean | null;
}

export type InputTypes =
  | 'text'
  | 'email'
  | 'password'
  | 'number'
  | 'file'
  | 'radio'
  | 'checkbox'
  | 'hidden'
  | 'color'
  | 'date'
  | 'time'
  | 'range';

export interface InputSpecs extends CommonElementSpecs {
  element: 'input';
  type?: InputTypes;
  placeholder?: string;
  autocomplete?: boolean;
  value?: string | number | boolean | null;
}

export type SelectOptions = { [key: string | number]: string | number };

export interface SelectAddOption {
  target: string;
  legend: string;
}

export interface SelectSpecs extends CommonElementSpecs {
  element: 'select';
  options: SelectOptions;
  selected?: string | number | null;
  displayId?: boolean;
  create?: SelectAddOption;
}

export type ElementSpecs = InputSpecs | HiddenInputSpecs | SelectSpecs;

export type FormSpecs = { [key: string]: ElementSpecs };
