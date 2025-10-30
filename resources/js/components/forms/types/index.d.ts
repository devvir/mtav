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
  min?: number;
  max?: number;
  minlength?: number;
  maxLength?: number;
  value?: string | number | boolean | null;
}

export type SelectOptions = { [key: string | number]: string | number };

export interface SelectAddOption {
  target: string;
  legend: string;
}

export interface CommonSelectSpecs extends CommonElementSpecs {
  element: 'select';
  options: SelectOptions;
  displayId?: boolean;
  create?: SelectAddOption;
}

export interface SingleSelectSpecs extends CommonSelectSpecs {
  multiple?: false;
  selected?: string | number | null;
}

export interface MultipleSelectSpecs extends CommonSelectSpecs {
  multiple: true;
  selected?: string[] | number[] | [];
}

export type SelectSpecs = SingleSelectSpecs | MultipleSelectSpecs;

export type ElementSpecs = InputSpecs | HiddenInputSpecs | SelectSpecs;

export type FormSpecs = Record<string, ElementSpecs>;

export type FormType = 'create' | 'edit';
